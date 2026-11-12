<?php

namespace App\Modules\EmailRelay\Services;

use App\Jobs\EmailRelay\ForwardRelayEmail;
use App\Jobs\EmailRelay\ProcessInboundRelayEmail;
use App\Jobs\EmailRelay\ProcessRelayReply;
use App\Models\SystemSetting;
use App\Modules\EmailRelay\Models\EmailRelayConversation;
use App\Modules\EmailRelay\Models\EmailRelayEvent;
use App\Modules\EmailRelay\Models\EmailRelayMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingContact;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Mime\Email;

class EmailRelayService
{
    public function isRelayGloballyEnabled(): bool
    {
        try {
            $setting = SystemSetting::where('key', 'email_relay_enabled')->value('value');
            if ($setting !== null) {
                return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
            }
        } catch (\Throwable $e) {
        }

        return (bool) config('email_relay.enabled', true);
    }

    public function getRelayDomain(): string
    {
        return strtolower((string) config('email_relay.domain', 'localdatehub.com'));
    }

    public function sanitizeHeader(string $value): string
    {
        return trim((string) preg_replace('/[\r\n\0]+/', ' ', $value));
    }

    public function calculateSpamScore(string $subject, string $body, string $senderEmail): int
    {
        $score = 0;
        $combined = strtolower($subject . ' ' . $body);

        // Header injection attempt
        if (preg_match('/(\r|\n|bcc:|cc:|content-type:)/i', $subject)) {
            $score += 80;
        }

        // Excessive URLs
        preg_match_all('/https?:\/\/[^\s]+/i', $body, $matches);
        if (count($matches[0] ?? []) >= 4) {
            $score += 40;
        }

        // Common spam/scam phrases
        foreach (['crypto giveaway', 'wire transfer', 'western union', 'bitcoin investment', 'click here to claim'] as $phrase) {
            if (str_contains($combined, $phrase)) {
                $score += 35;
            }
        }

        // Disposable/suspicious pattern
        if (str_ends_with(strtolower($senderEmail), '.ru') && str_contains($combined, 'http')) {
            $score += 20;
        }

        return min(100, $score);
    }

    public function extractEmailAddress(string $raw): string
    {
        if (preg_match('/<([^>]+)>/', $raw, $m)) {
            return strtolower(trim($m[1]));
        }

        return strtolower(trim($raw));
    }

    public function resolveListingContactByRelayEmail(string $recipientAddress): ?ListingContact
    {
        $normalized = $this->extractEmailAddress($recipientAddress);
        $domain = $this->getRelayDomain();

        if (!preg_match('/^relay-[a-z0-9]{6,32}@' . preg_quote($domain, '/') . '$/i', $normalized)) {
            return null;
        }

        $hash = hash('sha256', $normalized);

        $contact = ListingContact::with('listing.user')
            ->where('relay_token_hash', $hash)
            ->first();

        if (!$contact) {
            $contact = ListingContact::with('listing.user')
                ->whereRaw('LOWER(masked_relay_email) = ?', [$normalized])
                ->first();
        }

        if (!$contact || !$contact->allow_email_relay || $contact->relay_status !== 'active') {
            return null;
        }

        if (!$contact->listing || $contact->listing->status !== Listing::STATUS_ACTIVE) {
            return null;
        }

        return $contact;
    }

    public function resolveConversationByReplyAddress(string $recipientAddress): ?EmailRelayConversation
    {
        $normalized = $this->extractEmailAddress($recipientAddress);
        $domain = $this->getRelayDomain();

        if (!preg_match('/^reply-([a-z0-9]{12,40})@' . preg_quote($domain, '/') . '$/i', $normalized, $m)) {
            return null;
        }

        $hash = hash('sha256', strtolower($m[1]));

        return EmailRelayConversation::with(['listing.user', 'contact'])
            ->where('reply_token_hash', $hash)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Initiate an in-app email relay message from a listing page visitor.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $uploadedFiles
     */
    public function initiateInAppRelay(
        Listing $listing,
        array $data,
        array $uploadedFiles = [],
        ?string $senderIp = null
    ): EmailRelayMessage {
        if (!$this->isRelayGloballyEnabled()) {
            throw new RuntimeException('Email relay is currently disabled.');
        }

        $contact = $listing->contact;
        if (!$contact || !$contact->allow_email_relay || $contact->relay_status !== 'active') {
            throw new RuntimeException('Email relay is unavailable for this listing.');
        }

        $advertiserEmail = $contact->preferred_email_encrypted ?: $listing->user?->email;
        if (empty($advertiserEmail) || !filter_var($advertiserEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Advertiser email destination is not configured.');
        }

        $senderName = $this->sanitizeHeader((string) ($data['sender_name'] ?? 'Visitor'));
        $senderEmail = strtolower(trim($this->sanitizeHeader((string) $data['sender_email'])));
        $subject = $this->sanitizeHeader((string) ($data['subject'] ?? 'Inquiry: ' . $listing->title));
        $body = trim((string) ($data['message'] ?? ''));

        $spamScore = $this->calculateSpamScore($subject, $body, $senderEmail);
        $attachmentsMeta = $this->storeAttachments($uploadedFiles);
        $domain = $this->getRelayDomain();
        $messageId = '<msg-' . Str::uuid()->toString() . '@' . $domain . '>';

        $relayMessage = DB::transaction(function () use (
            $listing,
            $contact,
            $senderName,
            $senderEmail,
            $advertiserEmail,
            $subject,
            $body,
            $spamScore,
            $attachmentsMeta,
            $messageId,
            $senderIp
        ) {
            $convToken = strtolower(Str::random(24));
            $conversation = EmailRelayConversation::create([
                'listing_id' => $listing->id,
                'relay_contact_id' => $contact->id,
                'conversation_token' => $convToken,
                'reply_token_hash' => hash('sha256', $convToken),
                'visitor_email_encrypted' => $senderEmail,
                'visitor_name' => $senderName,
                'advertiser_email_encrypted' => strtolower(trim($advertiserEmail)),
                'subject' => Str::limit($subject, 250, ''),
                'last_message_id' => $messageId,
                'status' => 'active',
                'last_activity_at' => now(),
            ]);

            $status = $spamScore >= 70
                ? EmailRelayMessage::STATUS_BLOCKED
                : EmailRelayMessage::STATUS_QUEUED;

            $message = EmailRelayMessage::create([
                'listing_id' => $listing->id,
                'relay_contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'direction' => EmailRelayMessage::DIRECTION_OUTBOUND,
                'sender_name' => $senderName,
                'sender_email_encrypted' => $senderEmail,
                'recipient_email_encrypted' => strtolower(trim($advertiserEmail)),
                'subject' => Str::limit($subject, 250, ''),
                'message_body_encrypted' => $body,
                'provider' => (string) config('mail.default', 'smtp'),
                'message_id' => $messageId,
                'attachments_meta' => $attachmentsMeta,
                'delivery_status' => $status,
                'spam_score' => $spamScore,
                'error_message' => $status === EmailRelayMessage::STATUS_BLOCKED ? 'Blocked by automated spam/security filter.' : null,
                'sender_ip' => $senderIp,
                'received_at' => now(),
            ]);

            EmailRelayEvent::create([
                'relay_message_id' => $message->id,
                'event_type' => $status,
                'event_payload' => [
                    'source' => 'in_app_composer',
                    'spam_score' => $spamScore,
                ],
                'created_at' => now(),
            ]);

            return $message;
        });

        if ($relayMessage->delivery_status === EmailRelayMessage::STATUS_QUEUED) {
            try {
                $this->sendRelayForward($relayMessage, false);
            } catch (Throwable $e) {
                Log::warning('Immediate in-app relay delivery failed, queued for retry: ' . $e->getMessage());
                ForwardRelayEmail::dispatch($relayMessage->id)->onQueue((string) config('email_relay.queue', 'default'));
            }
        }

        return $relayMessage;
    }

    /**
     * Process an inbound email webhook from Cloudflare / Mailgun / Postmark / SendGrid.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processInboundWebhook(string $provider, array $payload): ?EmailRelayMessage
    {
        if (!$this->isRelayGloballyEnabled()) {
            return null;
        }

        $normalized = $this->normalizeInboundPayload($provider, $payload);
        $providerEventId = $normalized['provider_event_id'];
        $incomingMessageId = $normalized['message_id'] ?: ('<in-' . Str::uuid()->toString() . '@' . $this->getRelayDomain() . '>');

        // Idempotency: skip duplicate webhook or duplicate Message-ID
        if ($providerEventId && EmailRelayEvent::where('provider_event_id', $providerEventId)->exists()) {
            return EmailRelayMessage::where('provider_message_id', $normalized['provider_message_id'])
                ->orWhere('message_id', $incomingMessageId)
                ->first();
        }
        if (EmailRelayMessage::where('message_id', $incomingMessageId)->exists()) {
            return EmailRelayMessage::where('message_id', $incomingMessageId)->first();
        }

        $recipient = $normalized['to'];
        $senderEmail = $normalized['from'];
        $senderName = $this->sanitizeHeader($normalized['from_name'] ?: 'Email Sender');
        $subject = $this->sanitizeHeader($normalized['subject'] ?: 'Listing Inquiry');
        $body = trim($normalized['body']);

        // Case A: Reply to an existing conversation (`reply-<token>@domain`)
        $conversation = $this->resolveConversationByReplyAddress($recipient);
        if ($conversation) {
            $contact = $conversation->contact;
            if (!$contact || !$contact->allow_email_relay || $contact->relay_status !== 'active') {
                return null;
            }

            $advertiserEmail = strtolower(trim((string) $conversation->advertiser_email_encrypted));
            $visitorEmail = strtolower(trim((string) $conversation->visitor_email_encrypted));
            $isFromAdvertiser = ($senderEmail === $advertiserEmail);
            $targetEmail = $isFromAdvertiser ? $visitorEmail : $advertiserEmail;

            $spamScore = $this->calculateSpamScore($subject, $body, $senderEmail);
            $status = $spamScore >= 70 ? EmailRelayMessage::STATUS_BLOCKED : EmailRelayMessage::STATUS_RECEIVED;

            $message = DB::transaction(function () use (
                $conversation,
                $contact,
                $senderName,
                $senderEmail,
                $targetEmail,
                $subject,
                $body,
                $provider,
                $normalized,
                $incomingMessageId,
                $providerEventId,
                $status,
                $spamScore
            ) {
                $msg = EmailRelayMessage::create([
                    'listing_id' => $conversation->listing_id,
                    'relay_contact_id' => $contact->id,
                    'conversation_id' => $conversation->id,
                    'direction' => EmailRelayMessage::DIRECTION_INBOUND,
                    'sender_name' => $senderName,
                    'sender_email_encrypted' => $senderEmail,
                    'recipient_email_encrypted' => $targetEmail,
                    'subject' => Str::limit($subject, 250, ''),
                    'message_body_encrypted' => $body,
                    'provider' => $provider,
                    'provider_message_id' => $normalized['provider_message_id'],
                    'message_id' => $incomingMessageId,
                    'in_reply_to' => $normalized['in_reply_to'] ?: $conversation->last_message_id,
                    'references_header' => $normalized['references'] ?: $conversation->last_message_id,
                    'delivery_status' => $status,
                    'spam_score' => $spamScore,
                    'error_message' => $status === EmailRelayMessage::STATUS_BLOCKED ? 'Blocked by automated spam filter.' : null,
                    'received_at' => now(),
                ]);

                $conversation->update([
                    'last_message_id' => $incomingMessageId,
                    'last_activity_at' => now(),
                ]);

                EmailRelayEvent::create([
                    'relay_message_id' => $msg->id,
                    'event_type' => $status,
                    'provider_event_id' => $providerEventId,
                    'event_payload' => ['provider' => $provider, 'reply_routing' => true],
                    'created_at' => now(),
                ]);

                return $msg;
            });

            if ($message->delivery_status === EmailRelayMessage::STATUS_RECEIVED) {
                try {
                    $this->sendRelayForward($message, $isFromAdvertiser);
                } catch (Throwable $e) {
                    Log::warning('Immediate relay reply delivery failed, queued for retry: ' . $e->getMessage());
                    ProcessRelayReply::dispatch($message->id, $isFromAdvertiser)->onQueue((string) config('email_relay.queue', 'default'));
                }
            }

            return $message;
        }

        // Case B: Fresh email sent to `relay-<token>@domain`
        $contact = $this->resolveListingContactByRelayEmail($recipient);
        if (!$contact) {
            return null;
        }

        $advertiserEmail = strtolower(trim((string) ($contact->preferred_email_encrypted ?: $contact->listing?->user?->email)));
        if (empty($advertiserEmail) || !filter_var($advertiserEmail, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $spamScore = $this->calculateSpamScore($subject, $body, $senderEmail);
        $status = $spamScore >= 70 ? EmailRelayMessage::STATUS_BLOCKED : EmailRelayMessage::STATUS_RECEIVED;

        $message = DB::transaction(function () use (
            $contact,
            $senderName,
            $senderEmail,
            $advertiserEmail,
            $subject,
            $body,
            $provider,
            $normalized,
            $incomingMessageId,
            $providerEventId,
            $status,
            $spamScore
        ) {
            $convToken = strtolower(Str::random(24));
            $conversation = EmailRelayConversation::create([
                'listing_id' => $contact->listing_id,
                'relay_contact_id' => $contact->id,
                'conversation_token' => $convToken,
                'reply_token_hash' => hash('sha256', $convToken),
                'visitor_email_encrypted' => $senderEmail,
                'visitor_name' => $senderName,
                'advertiser_email_encrypted' => $advertiserEmail,
                'subject' => Str::limit($subject, 250, ''),
                'last_message_id' => $incomingMessageId,
                'status' => 'active',
                'last_activity_at' => now(),
            ]);

            $msg = EmailRelayMessage::create([
                'listing_id' => $contact->listing_id,
                'relay_contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'direction' => EmailRelayMessage::DIRECTION_INBOUND,
                'sender_name' => $senderName,
                'sender_email_encrypted' => $senderEmail,
                'recipient_email_encrypted' => $advertiserEmail,
                'subject' => Str::limit($subject, 250, ''),
                'message_body_encrypted' => $body,
                'provider' => $provider,
                'provider_message_id' => $normalized['provider_message_id'],
                'message_id' => $incomingMessageId,
                'in_reply_to' => $normalized['in_reply_to'],
                'references_header' => $normalized['references'],
                'delivery_status' => $status,
                'spam_score' => $spamScore,
                'error_message' => $status === EmailRelayMessage::STATUS_BLOCKED ? 'Blocked by automated spam filter.' : null,
                'received_at' => now(),
            ]);

            EmailRelayEvent::create([
                'relay_message_id' => $msg->id,
                'event_type' => $status,
                'provider_event_id' => $providerEventId,
                'event_payload' => ['provider' => $provider, 'reply_routing' => false],
                'created_at' => now(),
            ]);

            return $msg;
        });

        if ($message->delivery_status === EmailRelayMessage::STATUS_RECEIVED) {
            try {
                $this->sendRelayForward($message, false);
            } catch (Throwable $e) {
                Log::warning('Immediate inbound relay delivery failed, queued for retry: ' . $e->getMessage());
                ProcessInboundRelayEmail::dispatch($message->id)->onQueue((string) config('email_relay.queue', 'default'));
            }
        }

        return $message;
    }

    /**
     * Dispatch the actual forwarded email over SMTP/configured mailer without exposing private emails.
     */
    public function sendRelayForward(EmailRelayMessage $relayMessage, bool $isReplyToVisitor = false): void
    {
        $relayMessage->loadMissing(['listing', 'contact', 'conversation']);
        $listing = $relayMessage->listing;
        $contact = $relayMessage->contact;
        $conversation = $relayMessage->conversation;

        if (!$listing || !$contact || !$conversation) {
            throw new RuntimeException('Relay message is missing listing, contact, or conversation context.');
        }

        $recipientEmail = (string) $relayMessage->recipient_email_encrypted;
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid decrypted recipient email address.');
        }

        $domain = $this->getRelayDomain();
        $replyAddress = 'reply-' . $conversation->conversation_token . '@' . $domain;
        $maskedRelayEmail = $contact->masked_relay_email ?: ('relay-' . Str::random(10) . '@' . $domain);
        $fromAddress = (string) config('email_relay.from_address', config('mail.from.address', 'no_reply@' . $domain));
        $fromName = $isReplyToVisitor
            ? ($this->sanitizeHeader($listing->title) . ' via LocalDateHub')
            : ($this->sanitizeHeader($relayMessage->sender_name ?: 'LocalDateHub Visitor') . ' via LocalDateHub');

        $listingUrl = route('listings.show', $listing->slug);
        $bodyText = (string) $relayMessage->message_body_encrypted;
        $subjectLine = $this->sanitizeHeader((string) $relayMessage->subject);
        $htmlView = $isReplyToVisitor ? 'emails.relay.advertiser-reply' : 'emails.relay.advertiser-notification';
        $textView = $isReplyToVisitor ? 'emails.relay.advertiser-reply-text' : 'emails.relay.advertiser-notification-text';
        $outboundMessageId = '<out-' . Str::uuid()->toString() . '@' . $domain . '>';
        $inReplyTo = $relayMessage->in_reply_to ?: $conversation->last_message_id;
        $references = $this->formatReferencesHeader($relayMessage->references_header, $inReplyTo);

        Mail::send(['html' => $htmlView, 'text' => $textView], [
            'listingTitle' => $listing->title,
            'listingUrl' => $listingUrl,
            'senderDisplayName' => $relayMessage->sender_name ?: 'LocalDateHub Member',
            'subjectLine' => $subjectLine,
            'bodyText' => $bodyText,
            'maskedRelayEmail' => $maskedRelayEmail,
        ], function ($mail) use (
            $recipientEmail,
            $fromAddress,
            $fromName,
            $replyAddress,
            $subjectLine,
            $outboundMessageId,
            $inReplyTo,
            $references,
            $relayMessage
        ): void {
            $mail->to($recipientEmail)
                ->from($fromAddress, $fromName)
                ->replyTo($replyAddress, $fromName)
                ->subject($subjectLine);

            $symfonyMessage = $mail->getSymfonyMessage();
            $headers = $symfonyMessage->getHeaders();
            $headers->remove('Message-ID');
            $headers->addIdHeader('Message-ID', trim($outboundMessageId, '<>'));
            if ($inReplyTo) {
                $headers->addIdHeader('In-Reply-To', trim($inReplyTo, '<>'));
            }
            if ($references) {
                $headers->addTextHeader('References', $references);
            }

            foreach ((array) $relayMessage->attachments_meta as $attachment) {
                $diskPath = $attachment['storage_path'] ?? null;
                if ($diskPath && Storage::disk('local')->exists($diskPath)) {
                    $mail->attach(Storage::disk('local')->path($diskPath), [
                        'as' => $attachment['original_name'] ?? 'attachment',
                        'mime' => $attachment['mime_type'] ?? 'application/octet-stream',
                    ]);
                }
            }
        });

        $conversation->update([
            'last_message_id' => $outboundMessageId,
            'last_activity_at' => now(),
        ]);

        // Important: SMTP acceptance sets status to 'accepted', NOT 'delivered'.
        // 'delivered' is only set when a provider delivery webhook confirms final delivery.
        $relayMessage->update([
            'delivery_status' => EmailRelayMessage::STATUS_ACCEPTED,
            'sent_at' => now(),
            'error_message' => null,
        ]);

        EmailRelayEvent::create([
            'relay_message_id' => $relayMessage->id,
            'event_type' => EmailRelayMessage::STATUS_ACCEPTED,
            'event_payload' => [
                'outbound_message_id' => $outboundMessageId,
                'reply_to_visitor' => $isReplyToVisitor,
            ],
            'created_at' => now(),
        ]);

        // Send confirmation email to visitor on initial inquiry
        if (!$isReplyToVisitor && $relayMessage->direction === EmailRelayMessage::DIRECTION_OUTBOUND) {
            $visitorEmail = (string) $relayMessage->sender_email_encrypted;
            if (filter_var($visitorEmail, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::send('emails.relay.visitor-confirmation', [
                        'listingTitle' => $listing->title,
                        'listingUrl' => $listingUrl,
                        'subjectLine' => $subjectLine,
                        'maskedRelayEmail' => $maskedRelayEmail,
                    ], function ($mail) use ($visitorEmail, $fromAddress, $subjectLine): void {
                        $mail->to($visitorEmail)
                            ->from($fromAddress, (string) config('email_relay.from_name', 'LocalDateHub'))
                            ->subject('Confirmation: ' . $subjectLine);
                    });
                } catch (\Throwable $e) {
                    Log::warning('Visitor relay confirmation email failed', ['error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Handle delivery / bounce / failure webhook callbacks from email providers.
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordDeliveryStatusEvent(string $provider, array $payload): ?EmailRelayMessage
    {
        $eventId = (string) ($payload['event_id'] ?? $payload['id'] ?? $payload['RecordID'] ?? $payload['sg_event_id'] ?? '');
        if ($eventId !== '' && EmailRelayEvent::where('provider_event_id', $eventId)->exists()) {
            return null;
        }

        $rawEvent = strtolower((string) ($payload['event'] ?? $payload['event-data']['event'] ?? $payload['RecordType'] ?? $payload['type'] ?? ''));
        $providerMessageId = (string) ($payload['provider_message_id']
            ?? $payload['MessageID']
            ?? $payload['event-data']['message']['headers']['message-id']
            ?? $payload['message_id']
            ?? '');

        $message = null;
        if ($providerMessageId !== '') {
            $message = EmailRelayMessage::where('provider_message_id', $providerMessageId)
                ->orWhere('message_id', $providerMessageId)
                ->first();
        }
        if (!$message && !empty($payload['relay_message_id'])) {
            $message = EmailRelayMessage::find((int) $payload['relay_message_id']);
        }
        if (!$message) {
            return null;
        }

        $mappedStatus = match (true) {
            in_array($rawEvent, ['delivered', 'delivery'], true) => EmailRelayMessage::STATUS_DELIVERED,
            in_array($rawEvent, ['bounce', 'bounced', 'hard_bounce', 'soft_bounce'], true) => EmailRelayMessage::STATUS_BOUNCED,
            in_array($rawEvent, ['failed', 'dropped', 'rejected', 'spamcomplaint'], true) => EmailRelayMessage::STATUS_FAILED,
            default => null,
        };

        if (!$mappedStatus) {
            return $message;
        }

        $updates = ['delivery_status' => $mappedStatus];
        if ($mappedStatus === EmailRelayMessage::STATUS_DELIVERED) {
            $updates['delivered_at'] = now();
        } elseif (in_array($mappedStatus, [EmailRelayMessage::STATUS_BOUNCED, EmailRelayMessage::STATUS_FAILED], true)) {
            $updates['failed_at'] = now();
            $updates['error_message'] = Str::limit((string) ($payload['reason'] ?? $payload['Description'] ?? $payload['description'] ?? 'Provider reported ' . $rawEvent), 500);
        }

        $message->update($updates);

        EmailRelayEvent::create([
            'relay_message_id' => $message->id,
            'event_type' => $mappedStatus,
            'provider_event_id' => $eventId !== '' ? $eventId : null,
            'event_payload' => $payload,
            'created_at' => now(),
        ]);

        return $message;
    }

    public function retryMessage(EmailRelayMessage $message): void
    {
        $message->update([
            'delivery_status' => EmailRelayMessage::STATUS_QUEUED,
            'error_message' => null,
            'failed_at' => null,
        ]);

        EmailRelayEvent::create([
            'relay_message_id' => $message->id,
            'event_type' => 'retry_queued',
            'event_payload' => ['retried_at' => now()->toIso8601String()],
            'created_at' => now(),
        ]);

        $conversation = $message->conversation;
        $isReplyToVisitor = $conversation
            && strtolower((string) $message->recipient_email_encrypted) === strtolower((string) $conversation->visitor_email_encrypted);

        if ($message->direction === EmailRelayMessage::DIRECTION_INBOUND && $isReplyToVisitor) {
            ProcessRelayReply::dispatch($message->id, true)->onQueue((string) config('email_relay.queue', 'default'));
        } elseif ($message->direction === EmailRelayMessage::DIRECTION_INBOUND) {
            ProcessInboundRelayEmail::dispatch($message->id)->onQueue((string) config('email_relay.queue', 'default'));
        } else {
            ForwardRelayEmail::dispatch($message->id)->onQueue((string) config('email_relay.queue', 'default'));
        }
    }

    public function verifyWebhookSignature(string $provider, Request $request): bool
    {
        $provider = strtolower($provider);

        if ($provider === 'cloudflare') {
            $secret = (string) config('email_relay.webhooks.cloudflare.secret', '');
            if ($secret === '') {
                return false;
            }
            $headerToken = (string) ($request->header('X-Relay-Secret') ?: $request->bearerToken() ?: '');
            $signature = (string) $request->header('X-Relay-Signature', '');

            if ($headerToken !== '' && hash_equals($secret, $headerToken)) {
                return true;
            }
            if ($signature !== '') {
                $expected = hash_hmac('sha256', (string) $request->getContent(), $secret);
                return hash_equals($expected, $signature);
            }

            return false;
        }

        if ($provider === 'mailgun') {
            $signingKey = (string) config('email_relay.webhooks.mailgun.signing_key', '');
            if ($signingKey === '') {
                return false;
            }
            $sigBlock = $request->input('signature', []);
            $timestamp = (string) ($sigBlock['timestamp'] ?? $request->input('timestamp', ''));
            $token = (string) ($sigBlock['token'] ?? $request->input('token', ''));
            $signature = (string) ($sigBlock['signature'] ?? $request->input('signature', ''));

            if ($timestamp === '' || $token === '' || !is_string($signature) || $signature === '') {
                return false;
            }
            if (abs(time() - (int) $timestamp) > 900) {
                return false;
            }

            $expected = hash_hmac('sha256', $timestamp . $token, $signingKey);

            return hash_equals($expected, $signature);
        }

        if ($provider === 'postmark') {
            $token = (string) config('email_relay.webhooks.postmark.token', '');
            if ($token === '') {
                return false;
            }
            $provided = (string) ($request->header('X-Postmark-Token') ?: $request->query('token', ''));

            return $provided !== '' && hash_equals($token, $provided);
        }

        if ($provider === 'sendgrid') {
            $secret = (string) config('email_relay.webhooks.sendgrid.secret', '');
            if ($secret === '') {
                return false;
            }
            $provided = (string) ($request->header('X-Relay-Secret') ?: $request->query('token', ''));

            return $provided !== '' && hash_equals($secret, $provided);
        }

        return false;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array<string, mixed>>
     */
    protected function storeAttachments(array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }
            $ext = strtolower($file->getClientOriginalExtension());
            $safeName = Str::uuid()->toString() . ($ext ? '.' . $ext : '');
            $path = $file->storeAs('email_relay_attachments/' . date('Y/m'), $safeName, 'local');

            $stored[] = [
                'original_name' => Str::limit(basename($file->getClientOriginalName()), 120, ''),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ];
        }

        return $stored;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizeInboundPayload(string $provider, array $payload): array
    {
        return match (strtolower($provider)) {
            'mailgun' => [
                'to' => $this->extractEmailAddress((string) ($payload['recipient'] ?? $payload['To'] ?? '')),
                'from' => $this->extractEmailAddress((string) ($payload['sender'] ?? $payload['from'] ?? $payload['From'] ?? '')),
                'from_name' => (string) ($payload['from_name'] ?? ''),
                'subject' => (string) ($payload['subject'] ?? $payload['Subject'] ?? 'Listing Inquiry'),
                'body' => (string) ($payload['stripped-text'] ?? $payload['body-plain'] ?? strip_tags((string) ($payload['body-html'] ?? ''))),
                'message_id' => (string) ($payload['Message-Id'] ?? $payload['message-id'] ?? ''),
                'in_reply_to' => (string) ($payload['In-Reply-To'] ?? ''),
                'references' => (string) ($payload['References'] ?? ''),
                'provider_message_id' => (string) ($payload['Message-Id'] ?? $payload['message-id'] ?? ''),
                'provider_event_id' => (string) ($payload['event_id'] ?? $payload['Message-Id'] ?? ''),
            ],
            'postmark' => [
                'to' => $this->extractEmailAddress((string) ($payload['To'] ?? $payload['OriginalRecipient'] ?? '')),
                'from' => $this->extractEmailAddress((string) ($payload['From'] ?? '')),
                'from_name' => (string) ($payload['FromName'] ?? ''),
                'subject' => (string) ($payload['Subject'] ?? 'Listing Inquiry'),
                'body' => (string) ($payload['TextBody'] ?? strip_tags((string) ($payload['HtmlBody'] ?? ''))),
                'message_id' => (string) ($payload['MessageID'] ?? ''),
                'in_reply_to' => (string) ($payload['Headers']['In-Reply-To'] ?? ''),
                'references' => (string) ($payload['Headers']['References'] ?? ''),
                'provider_message_id' => (string) ($payload['MessageID'] ?? ''),
                'provider_event_id' => (string) ($payload['MessageID'] ?? ''),
            ],
            default => [ // cloudflare / sendgrid / generic JSON
                'to' => $this->extractEmailAddress((string) ($payload['to'] ?? $payload['recipient'] ?? '')),
                'from' => $this->extractEmailAddress((string) ($payload['from'] ?? $payload['sender'] ?? '')),
                'from_name' => (string) ($payload['from_name'] ?? ''),
                'subject' => (string) ($payload['subject'] ?? 'Listing Inquiry'),
                'body' => (string) ($payload['text'] ?? $payload['body'] ?? strip_tags((string) ($payload['html'] ?? ''))),
                'message_id' => (string) ($payload['message_id'] ?? ''),
                'in_reply_to' => (string) ($payload['in_reply_to'] ?? ''),
                'references' => (string) ($payload['references'] ?? ''),
                'provider_message_id' => (string) ($payload['provider_message_id'] ?? $payload['message_id'] ?? ''),
                'provider_event_id' => (string) ($payload['provider_event_id'] ?? $payload['event_id'] ?? $payload['message_id'] ?? ''),
            ],
        };
    }

    /**
     * Format RFC 5322 References header ensuring each ID token is cleanly wrapped in <...>.
     */
    protected function formatReferencesHeader(?string $references, ?string $inReplyTo): string
    {
        $raw = trim(($references ? $references . ' ' : '') . ($inReplyTo ?: ''));
        if ($raw === '') {
            return '';
        }

        preg_match_all('/<([^>]+)>|([^\s<>]+)/', $raw, $matches);
        $ids = [];
        foreach ($matches[0] as $candidate) {
            $cleaned = trim($candidate, " \t\n\r\0\x0B<>");
            if ($cleaned !== '' && !in_array("<{$cleaned}>", $ids, true)) {
                $ids[] = "<{$cleaned}>";
            }
        }

        return implode(' ', $ids);
    }
}


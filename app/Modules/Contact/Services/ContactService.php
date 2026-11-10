<?php

namespace App\Modules\Contact\Services;

use App\Models\User;
use App\Modules\Contact\DTOs\ContactMessageData;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Moderation\Models\ContentReport;
use App\Support\Exceptions\ProhibitedContentException;
use InvalidArgumentException;

class ContactService
{
    /**
     * Prohibited commercial solicitation, illegal sexual services, and financial scam patterns.
     */
    protected array $prohibitedPatterns = [
        // Commercial Sex Work & Prostitution (FOSTA-SESTA)
        '/\b(escort|prostitut|ppm|hourly\s*rate|pay\s*per\s*meet|happy\s*ending|incall|outcall|sensual\s*massage|full\s*service|massage\s*parlor)\b/i',
        '/\b(rates?\s*available|donation\s*required|cash\s*required\s*for\s*time|call\s*girl)\b/i',
        // Financial & Romance Scams
        '/\b(wire\s*transfer|cash\s*app|venmo|gift\s*card|crypto|bitcoin|western\s*union|zelle|money\s*order|send\s*money|advance\s*fee)\b/i',
        // Extortion & Blackmail
        '/\b(blackmail|extort|send\s*nudes\s*or\s*else|expose\s*you|leak\s*photos)\b/i',
    ];

    /**
     * Validate message content for user safety, prohibited commercial solicitation, and scam patterns.
     *
     * @throws ProhibitedContentException
     */
    public function validateMessageSafety(string $content): void
    {
        $normalized = preg_replace('/\s+/', ' ', trim($content));

        foreach ($this->prohibitedPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                throw new ProhibitedContentException(
                    'Safety Alert: In compliance with federal laws (including FOSTA-SESTA) and community guidelines, commercial adult solicitations, compensated services, financial wire requests, and harassment are strictly prohibited.'
                );
            }
        }
    }

    /**
     * Legacy DTO wrapper for backward compatibility.
     */
    public function validateMessageSafetyData(ContactMessageData $data): void
    {
        $this->validateMessageSafety($data->content);
    }

    /**
     * Send an encrypted, rate-limited inquiry from a user/guest to a listing advertiser.
     *
     * @throws ProhibitedContentException
     * @throws InvalidArgumentException
     */
    public function sendListingInquiry(
        Listing $listing,
        array $validated,
        ?User $sender = null,
        ?string $clientIp = null,
        ?string $userAgent = null
    ): ContactMessage {
        if (!$listing->isActive()) {
            throw new InvalidArgumentException('Inquiries cannot be submitted to non-active or expired listings.');
        }

        if ($listing->contact && !$listing->contact->allow_platform_messages) {
            throw new InvalidArgumentException('The advertiser has disabled platform contact messages for this listing.');
        }

        // Validate prohibited words
        $this->validateMessageSafety($validated['message']);

        // Create encrypted message
        $message = ContactMessage::create([
            'listing_id' => $listing->id,
            'recipient_id' => $listing->user_id,
            'sender_id' => $sender?->id,
            'inquiry_type' => 'listing_inquiry',
            'sender_name' => $validated['name'],
            'sender_email_encrypted' => $validated['email'],
            'sender_phone_encrypted' => $validated['phone'] ?? null,
            'subject' => "Inquiry regarding: {$listing->title}",
            'message_encrypted' => $validated['message'],
            'is_read' => false,
            'is_replied' => false,
            'is_flagged' => false,
            'client_ip' => $clientIp,
            'user_agent' => substr((string) $userAgent, 0, 255),
        ]);

        // Increment listing reply counter and record analytics
        $listing->increment('reply_count');
        $listing->recordAnalyticsEvent('contact_clicks');

        // Queue notification to listing owner
        if ($listing->user) {
            $notificationService = app(\App\Modules\Notifications\Services\NotificationService::class);
            $notificationService->send(
                $listing->user,
                new \App\Modules\Notifications\Notifications\ContactMessageReceivedNotification($message),
                'contact_received',
                "New inquiry on: {$listing->title}",
                $validated['message']
            );
        }

        return $message;
    }

    /**
     * Submit an abuse or safety violation report against a listing.
     */
    public function reportListingAbuse(
        Listing $listing,
        array $validated,
        ?User $reporter = null,
        ?string $clientIp = null,
        ?string $userAgent = null
    ): ContactMessage {
        $reason = $validated['report_reason'];
        $details = $validated['report_details'];

        $report = ContactMessage::create([
            'listing_id' => $listing->id,
            'recipient_id' => $listing->user_id,
            'sender_id' => $reporter?->id,
            'inquiry_type' => 'safety_report',
            'is_flagged' => true,
            'flagged_reason' => $reason,
            'sender_name' => $validated['reporter_name'] ?? ($reporter?->name ?? 'Confidential Reporter'),
            'sender_email_encrypted' => $validated['reporter_email'] ?? ($reporter?->email ?? 'reporter@confidential.localdatehub.com'),
            'subject' => "Safety Report [{$reason}]: {$listing->title}",
            'message_encrypted' => $details,
            'client_ip' => $clientIp,
            'user_agent' => substr((string) $userAgent, 0, 255),
        ]);

        $riskLevel = match ($reason) {
            'underage_minor', 'underage_suspected', 'human_trafficking', 'non_consensual' => 'critical',
            'commercial_sex', 'commercial_solicitation', 'violence_threat', 'fraud_scam' => 'high',
            'harassment', 'prohibited_goods' => 'medium',
            default => 'low',
        };

        $isUrgent = in_array($riskLevel, ['critical'], true);

        // Record real-time incident in ContentReport for the Trust & Safety team and Admin Hub
        ContentReport::create([
            'reporter_id' => $reporter?->id,
            'reportable_type' => Listing::class,
            'reportable_id' => $listing->id,
            'reason_code' => $reason,
            'details' => $details,
            'evidence_urls' => [],
            'status' => 'open',
            'risk_level' => $riskLevel,
            'is_urgent_takedown' => $isUrgent,
            'reporter_ip' => $clientIp,
        ]);

        // Zero-tolerance escalation: immediately put listing into pending review if report is severe
        if (in_array($reason, ['underage_minor', 'commercial_sex'], true)) {
            $listing->status = Listing::STATUS_PENDING_REVIEW;
            $listing->save();
            $listing->recordStatusChange(
                Listing::STATUS_PENDING_REVIEW,
                "Automatic moderation hold triggered by high-severity report: [{$reason}]."
            );
        }

        return $report;
    }

    /**
     * Generate privacy-protecting masked relay email for listing contacts.
     */
    public function generateMaskedRelayEmail(Listing $listing): string
    {
        $hash = substr(hash('sha256', "listing-{$listing->id}-" . config('app.key')), 0, 8);
        return "relay-{$hash}@reply.localdatehub.com";
    }

    /**
     * Toggle saved favorite status for a listing by an authenticated user.
     */
    public function toggleFavorite(Listing $listing, User $user): bool
    {
        $existing = $user->favorites()->where('listing_id', $listing->id)->exists();

        if ($existing) {
            $user->favorites()->detach($listing->id);
            if ($listing->favorite_count > 0) {
                $listing->decrement('favorite_count');
            }
            return false;
        }

        $user->favorites()->attach($listing->id);
        $listing->increment('favorite_count');
        $listing->recordAnalyticsEvent('favorite_adds');
        return true;
    }
}

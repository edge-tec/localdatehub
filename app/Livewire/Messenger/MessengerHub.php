<?php

namespace App\Livewire\Messenger;

use App\Models\User;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Notifications\Models\NotificationLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class MessengerHub extends Component
{
    use WithFileUploads;

    // Active conversation state
    public ?string $activeConversationKey = null;
    public ?int $activePeerId = null;
    public ?int $activeListingId = null;

    // Filters and Search
    public string $activeFilter = 'all'; // all, unread, buying, selling, starred, archived
    public string $searchQuery = '';

    // Composer
    public string $messageText = '';
    public $attachment = null;
    public ?int $replyToMessageId = null;
    public ?string $replyToMessageSnippet = null;

    // Panels visibility & responsive state
    public bool $showRightPanel = true;
    public string $mobileView = 'chat'; // 'inbox', 'chat', 'details'

    // Modals
    public bool $showNewMessageModal = false;
    public string $newRecipientSearch = '';
    public ?int $selectedListingIdForNew = null;
    public ?int $selectedRecipientIdForNew = null;
    public string $newDirectMessageText = '';

    public bool $blockConfirmModal = false;
    public bool $reportModal = false;
    public string $reportReason = 'spam';
    public string $reportNotes = '';
    public bool $deleteConfirmModal = false;

    // Right panel media tab
    public string $mediaTab = 'photos'; // photos, files

    protected $queryString = [
        'activeConversationKey' => ['except' => '', 'as' => 'c'],
        'activeFilter' => ['except' => 'all', 'as' => 'filter'],
    ];

    public function mount(?int $conversation = null, ?int $listing = null, ?int $user = null): void
    {
        $authId = Auth::id();
        if (!$authId) {
            return;
        }

        // Initialize target conversation from parameters if requested
        if ($user && $user !== $authId) {
            $key = $listing ? "{$user}_{$listing}" : "{$user}_0";
            $this->selectConversation($key);
        } elseif ($listing) {
            $listingModel = Listing::find($listing);
            if ($listingModel && $listingModel->user_id !== $authId) {
                $key = "{$listingModel->user_id}_{$listing}";
                $this->selectConversation($key);
            }
        } elseif ($conversation) {
            $msg = ContactMessage::find($conversation);
            if ($msg && ($msg->sender_id === $authId || $msg->recipient_id === $authId)) {
                $peerId = $msg->sender_id === $authId ? $msg->recipient_id : $msg->sender_id;
                $key = "{$peerId}_" . ($msg->listing_id ?: 0);
                $this->selectConversation($key);
            }
        }

        // Default to first conversation if none selected
        if (!$this->activeConversationKey) {
            $conversations = $this->getConversationsProperty();
            if (!empty($conversations)) {
                $first = reset($conversations);
                $this->selectConversation($first['key']);
            } else {
                $this->mobileView = 'inbox';
            }
        }
    }

    /**
     * Compute conversation summary list for current user.
     */
    public function getConversationsProperty(): array
    {
        $authId = Auth::id();
        if (!$authId) {
            return [];
        }

        // Query all messages sent or received by current user
        $messages = ContactMessage::query()
            ->where(function ($q) use ($authId) {
                $q->where('sender_id', $authId)
                  ->orWhere('recipient_id', $authId);
            })
            ->with(['sender.profile', 'recipient.profile', 'listing.primaryPhoto'])
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = [];

        foreach ($messages as $msg) {
            $peerId = ($msg->sender_id === $authId) ? $msg->recipient_id : $msg->sender_id;
            if (!$peerId) {
                continue;
            }

            $listingId = $msg->listing_id ?: 0;
            $key = "{$peerId}_{$listingId}";

            if (!isset($grouped[$key])) {
                $peer = ($msg->sender_id === $authId) ? $msg->recipient : $msg->sender;
                $listing = $msg->listing;

                $grouped[$key] = [
                    'key' => $key,
                    'peer_id' => $peerId,
                    'peer' => $peer,
                    'listing_id' => $listingId,
                    'listing' => $listing,
                    'latest_message' => $msg,
                    'unread_count' => 0,
                    'is_pinned' => false,
                    'is_starred' => false,
                    'is_archived' => false,
                    'is_selling' => ($listing && $listing->user_id === $authId),
                    'is_buying' => ($listing && $listing->user_id !== $authId),
                    'total_messages' => 0,
                ];
            }

            $grouped[$key]['total_messages']++;

            // Count unread messages sent by peer to auth user
            if ($msg->recipient_id === $authId && !$msg->is_read) {
                $grouped[$key]['unread_count']++;
            }

            if ($msg->is_pinned) {
                $grouped[$key]['is_pinned'] = true;
            }
            if ($msg->is_starred) {
                $grouped[$key]['is_starred'] = true;
            }
            if ($msg->is_archived) {
                $grouped[$key]['is_archived'] = true;
            }
        }

        $conversations = array_values($grouped);

        // Apply filters
        if ($this->activeFilter === 'unread') {
            $conversations = array_filter($conversations, fn($c) => $c['unread_count'] > 0);
        } elseif ($this->activeFilter === 'buying') {
            $conversations = array_filter($conversations, fn($c) => $c['is_buying']);
        } elseif ($this->activeFilter === 'selling') {
            $conversations = array_filter($conversations, fn($c) => $c['is_selling']);
        } elseif ($this->activeFilter === 'starred') {
            $conversations = array_filter($conversations, fn($c) => $c['is_starred']);
        } elseif ($this->activeFilter === 'archived') {
            $conversations = array_filter($conversations, fn($c) => $c['is_archived']);
        } else {
            // "all" view hides archived threads by default unless requested
            $conversations = array_filter($conversations, fn($c) => !$c['is_archived']);
        }

        // Apply search query
        if (!empty(trim($this->searchQuery))) {
            $search = mb_strtolower(trim($this->searchQuery));
            $conversations = array_filter($conversations, function ($c) use ($search) {
                $peerName = mb_strtolower($c['peer']?->name ?? '');
                $listingTitle = mb_strtolower($c['listing']?->title ?? '');
                $lastMsg = mb_strtolower($c['latest_message']?->message_encrypted ?? '');
                return str_contains($peerName, $search) || str_contains($listingTitle, $search) || str_contains($lastMsg, $search);
            });
        }

        // Sort: pinned threads float to the top, then newest message first
        usort($conversations, function ($a, $b) {
            if ($a['is_pinned'] !== $b['is_pinned']) {
                return $b['is_pinned'] <=> $a['is_pinned'];
            }
            return $b['latest_message']->created_at <=> $a['latest_message']->created_at;
        });

        return array_values($conversations);
    }

    /**
     * Active peer user model.
     */
    public function getActivePeerProperty(): ?User
    {
        return $this->activePeerId ? User::find($this->activePeerId) : null;
    }

    /**
     * Active listing model.
     */
    public function getActiveListingProperty(): ?Listing
    {
        return $this->activeListingId ? Listing::with('primaryPhoto', 'city', 'state')->find($this->activeListingId) : null;
    }

    /**
     * Active message thread list.
     */
    public function getActiveMessagesProperty(): array
    {
        $authId = Auth::id();
        if (!$authId || !$this->activePeerId) {
            return [];
        }

        $query = ContactMessage::query()
            ->where(function ($q) use ($authId) {
                $q->where(function ($sub) use ($authId) {
                    $sub->where('sender_id', $authId)
                        ->where('recipient_id', $this->activePeerId);
                })->orWhere(function ($sub) use ($authId) {
                    $sub->where('sender_id', $this->activePeerId)
                        ->where('recipient_id', $authId);
                });
            });

        if ($this->activeListingId) {
            $query->where('listing_id', $this->activeListingId);
        }

        return $query->with('replyTo')
            ->orderBy('created_at', 'asc')
            ->get()
            ->all();
    }

    /**
     * Shared media in active conversation.
     */
    public function getSharedMediaProperty(): array
    {
        $authId = Auth::id();
        if (!$authId || !$this->activePeerId) {
            return [];
        }

        $query = ContactMessage::query()
            ->whereNotNull('attachment_path')
            ->where(function ($q) use ($authId) {
                $q->where(function ($sub) use ($authId) {
                    $sub->where('sender_id', $authId)
                        ->where('recipient_id', $this->activePeerId);
                })->orWhere(function ($sub) use ($authId) {
                    $sub->where('sender_id', $this->activePeerId)
                        ->where('recipient_id', $authId);
                });
            });

        if ($this->activeListingId) {
            $query->where('listing_id', $this->activeListingId);
        }

        if ($this->mediaTab === 'photos') {
            $query->where('attachment_type', 'image');
        } elseif ($this->mediaTab === 'files') {
            $query->where('attachment_type', '!=', 'image');
        }

        return $query->orderBy('created_at', 'desc')->get()->all();
    }

    /**
     * Check if active conversation is currently starred.
     */
    public function getIsStarredProperty(): bool
    {
        $authId = Auth::id();
        if (!$authId || !$this->activePeerId) return false;

        return ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->where('is_starred', true)
        ->exists();
    }

    /**
     * Check if active conversation is currently pinned.
     */
    public function getIsPinnedProperty(): bool
    {
        $authId = Auth::id();
        if (!$authId || !$this->activePeerId) return false;

        return ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->where('is_pinned', true)
        ->exists();
    }

    /**
     * Check if active conversation is currently archived.
     */
    public function getIsArchivedProperty(): bool
    {
        $authId = Auth::id();
        if (!$authId || !$this->activePeerId) return false;

        return ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->where('is_archived', true)
        ->exists();
    }

    /**
     * Check if active peer user is currently blocked by authenticated user.
     */
    public function getIsUserBlockedProperty(): bool
    {
        $authId = Auth::id();
        if (!$authId || !$this->activePeerId) return false;

        try {
            return DB::table('user_blocks')
                ->where('blocker_id', $authId)
                ->where('blocked_id', $this->activePeerId)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Select and open a conversation.
     */
    public function selectConversation(string $key): void
    {
        $parts = explode('_', $key);
        $peerId = (int) ($parts[0] ?? 0);
        $listingId = (int) ($parts[1] ?? 0);

        if (!$peerId) {
            return;
        }

        $this->activeConversationKey = $key;
        $this->activePeerId = $peerId;
        $this->activeListingId = $listingId ?: null;
        $this->mobileView = 'chat';
        $this->replyToMessageId = null;
        $this->replyToMessageSnippet = null;

        // Mark incoming unread messages as read
        $authId = Auth::id();
        ContactMessage::where('sender_id', $peerId)
            ->where('recipient_id', $authId)
            ->when($listingId, fn($q) => $q->where('listing_id', $listingId))
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $this->dispatch('scroll-to-bottom');
    }

    /**
     * Send a message in the active conversation.
     */
    public function sendMessage(): void
    {
        $this->validate([
            'messageText' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:15360'], // 15MB limit
        ]);

        $text = trim($this->messageText);
        if ($text === '' && !$this->attachment) {
            return;
        }

        $authId = Auth::id();
        $authUser = Auth::user();

        if (!$this->activePeerId || !$authUser) {
            return;
        }

        $attachmentPath = null;
        $attachmentType = null;
        $attachmentName = null;
        $attachmentSize = null;

        if ($this->attachment) {
            $originalName = $this->attachment->getClientOriginalName();
            $mime = $this->attachment->getMimeType();
            $size = $this->attachment->getSize();
            $isSanitizableImage = in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
            $isImage = $isSanitizableImage || str_starts_with((string) $mime, 'image/');

            if ($isSanitizableImage) {
                try {
                    $stored = app(\App\Support\Storage\MediaStorageService::class)->storeSanitizedPhoto(
                        $this->attachment,
                        'attachments',
                        'public',
                        1600,
                        1600
                    );
                    $attachmentPath = $stored['path'];
                    $attachmentSize = $stored['size'];
                } catch (\Throwable $e) {
                    $this->addError('attachment', 'The uploaded image is corrupted or invalid. Please choose a valid JPG, PNG, or WebP.');
                    return;
                }
            } else {
                $attachmentPath = $this->attachment->store('attachments', 'public');
                $attachmentSize = $size;
            }

            $attachmentType = $isImage ? 'image' : 'file';
            $attachmentName = $originalName;
        }

        $subject = $this->activeListing
            ? "Inquiry regarding: {$this->activeListing->title}"
            : "Direct message from {$authUser->name}";

        $message = ContactMessage::create([
            'listing_id' => $this->activeListingId,
            'sender_id' => $authId,
            'recipient_id' => $this->activePeerId,
            'inquiry_type' => $this->activeListingId ? 'listing_inquiry' : 'direct_chat',
            'reply_to_id' => $this->replyToMessageId,
            'sender_name' => $authUser->name,
            'sender_email_encrypted' => $authUser->email,
            'sender_phone_encrypted' => $authUser->profile?->phone,
            'subject' => $subject,
            'message_encrypted' => $text,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'attachment_name' => $attachmentName,
            'attachment_size' => $attachmentSize,
            'is_read' => false,
            'is_replied' => true,
            'client_ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ]);

        // Clear composer state
        $this->messageText = '';
        $this->attachment = null;
        $this->replyToMessageId = null;
        $this->replyToMessageSnippet = null;

        // Notify recipient in database
        try {
            NotificationLog::create([
                'user_id' => $this->activePeerId,
                'channel' => 'database',
                'notification_type' => 'message_reply',
                'subject' => "New message from {$authUser->name}",
                'message' => Str::limit($text ?: 'Sent an attachment', 150),
                'recipient_target' => $this->activePeer?->email,
                'status' => 'sent',
                'is_read' => false,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Notification fallback
        }

        $this->dispatch('scroll-to-bottom');
    }

    /**
     * Set reply to a specific message.
     */
    public function setReplyTo(int $messageId): void
    {
        $msg = ContactMessage::find($messageId);
        if ($msg) {
            $this->replyToMessageId = $msg->id;
            $this->replyToMessageSnippet = Str::limit($msg->message_encrypted ?: ($msg->attachment_name ?: 'Attachment'), 60);
        }
    }

    /**
     * Clear reply quote.
     */
    public function clearReply(): void
    {
        $this->replyToMessageId = null;
        $this->replyToMessageSnippet = null;
    }

    /**
     * Remove staged attachment before sending.
     */
    public function removeAttachment(): void
    {
        $this->attachment = null;
    }

    /**
     * Toggle emoji reaction on a message.
     */
    public function toggleReaction(int $messageId, string $emoji): void
    {
        $authId = Auth::id();
        $message = ContactMessage::find($messageId);
        if (!$message) {
            return;
        }

        $reactions = $message->reactions ?: [];
        $existing = $reactions[$emoji] ?? [];

        if (in_array($authId, $existing)) {
            // Remove reaction
            $reactions[$emoji] = array_values(array_diff($existing, [$authId]));
            if (empty($reactions[$emoji])) {
                unset($reactions[$emoji]);
            }
        } else {
            // Add reaction
            $reactions[$emoji][] = $authId;
        }

        $message->reactions = $reactions;
        $message->save();
    }

    /**
     * Toggle Star on conversation.
     */
    public function toggleStarConversation(): void
    {
        $authId = Auth::id();
        if (!$this->activePeerId) return;

        $newState = !$this->isStarred;

        ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->update(['is_starred' => $newState]);

        session()->flash('messenger_toast', $newState ? '⭐ Conversation starred!' : 'Conversation unstarred.');
    }

    /**
     * Toggle Pin conversation.
     */
    public function togglePinConversation(): void
    {
        $authId = Auth::id();
        if (!$this->activePeerId) return;

        $newState = !$this->isPinned;

        ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->update(['is_pinned' => $newState]);

        session()->flash('messenger_toast', $newState ? '📌 Conversation pinned to top!' : 'Conversation unpinned.');
    }

    /**
     * Toggle Archive conversation.
     */
    public function toggleArchiveConversation(): void
    {
        $authId = Auth::id();
        if (!$this->activePeerId) return;

        $newState = !$this->isArchived;

        ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->update(['is_archived' => $newState]);

        session()->flash('messenger_toast', $newState ? '📁 Conversation archived.' : 'Conversation restored from archive.');
    }

    /**
     * Delete entire conversation thread.
     */
    public function deleteConversation(): void
    {
        $authId = Auth::id();
        if (!$this->activePeerId) return;

        ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->delete();

        $this->deleteConfirmModal = false;
        $this->activeConversationKey = null;
        $this->activePeerId = null;
        $this->activeListingId = null;
        $this->mobileView = 'inbox';

        session()->flash('messenger_toast', '🗑️ Conversation deleted.');
    }

    /**
     * Delete single message (own messages only).
     */
    public function deleteMessage(int $messageId): void
    {
        $authId = Auth::id();
        $msg = ContactMessage::where('id', $messageId)->where('sender_id', $authId)->first();
        if ($msg) {
            $msg->delete();
        }
    }

    /**
     * Submit user report.
     */
    public function submitReport(): void
    {
        $authId = Auth::id();
        if (!$this->activePeerId || !$authId) return;

        try {
            DB::table('content_reports')->insert([
                'reporter_id' => $authId,
                'reportable_type' => \App\Models\User::class,
                'reportable_id' => $this->activePeerId,
                'reason_code' => $this->reportReason ?: 'other',
                'details' => $this->reportNotes ?: 'User reported via Messenger interface',
                'status' => 'open',
                'reporter_ip' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Report fallback
        }

        $this->reportModal = false;
        $this->reportNotes = '';
        session()->flash('messenger_toast', '🛡️ Report submitted to Trust & Safety team.');
    }

    /**
     * Block user.
     */
    public function confirmBlockUser(): void
    {
        $authId = Auth::id();
        if (!$this->activePeerId || !$authId) return;

        try {
            DB::table('user_blocks')->updateOrInsert(
                [
                    'blocker_id' => $authId,
                    'blocked_id' => $this->activePeerId,
                ],
                [
                    'reason' => 'User blocked via Messenger',
                    'created_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // Block fallback
        }

        $this->blockConfirmModal = false;
        session()->flash('messenger_toast', '🚫 User has been blocked.');
    }

    /**
     * Unblock user.
     */
    public function unblockUser(): void
    {
        $authId = Auth::id();
        if (!$this->activePeerId || !$authId) return;

        try {
            DB::table('user_blocks')
                ->where('blocker_id', $authId)
                ->where('blocked_id', $this->activePeerId)
                ->delete();
        } catch (\Throwable $e) {
            // Unblock fallback
        }

        session()->flash('messenger_toast', 'User unblocked.');
    }

    /**
     * Toggle right panel info.
     */
    public function toggleRightPanel(): void
    {
        $this->showRightPanel = !$this->showRightPanel;
    }

    /**
     * Responsive mobile navigation helpers.
     */
    public function backToInbox(): void
    {
        $this->mobileView = 'inbox';
    }

    public function backToChat(): void
    {
        $this->mobileView = 'chat';
    }

    public function openMobileDetails(): void
    {
        $this->mobileView = 'details';
    }

    public function render()
    {
        return view('livewire.messenger.messenger-hub', [
            'conversations' => $this->conversations,
            'activePeer' => $this->activePeer,
            'activeListing' => $this->activeListing,
            'activeMessages' => $this->activeMessages,
            'sharedMedia' => $this->sharedMedia,
        ]);
    }
}

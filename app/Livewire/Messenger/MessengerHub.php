<?php

namespace App\Livewire\Messenger;

use App\Models\User;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use App\Modules\Notifications\Models\NotificationLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    // Panels visibility
    public bool $showRightPanel = true;
    public bool $showMobileChat = false;

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

        // Filter by tab
        $filtered = array_filter($grouped, function ($c) {
            return match ($this->activeFilter) {
                'unread' => $c['unread_count'] > 0,
                'buying' => $c['is_buying'],
                'selling' => $c['is_selling'],
                'starred' => $c['is_starred'],
                'archived' => $c['is_archived'],
                default => !$c['is_archived'], // 'all' excludes archived
            };
        });

        // Filter by search query
        if (!empty(trim($this->searchQuery))) {
            $query = strtolower(trim($this->searchQuery));
            $filtered = array_filter($filtered, function ($c) use ($query) {
                $peerName = strtolower($c['peer']?->name ?? '');
                $listingTitle = strtolower($c['listing']?->title ?? '');
                $latestSnippet = strtolower($c['latest_message']?->message_encrypted ?? '');
                return str_contains($peerName, $query)
                    || str_contains($listingTitle, $query)
                    || str_contains($latestSnippet, $query);
            });
        }

        // Sort: pinned first, then newest message
        uasort($filtered, function ($a, $b) {
            if ($a['is_pinned'] !== $b['is_pinned']) {
                return $b['is_pinned'] <=> $a['is_pinned'];
            }
            return $b['latest_message']->created_at <=> $a['latest_message']->created_at;
        });

        return $filtered;
    }

    /**
     * Active Peer model.
     */
    public function getActivePeerProperty(): ?User
    {
        return $this->activePeerId ? User::with('profile')->find($this->activePeerId) : null;
    }

    /**
     * Active Listing model.
     */
    public function getActiveListingProperty(): ?Listing
    {
        return $this->activeListingId ? Listing::with(['primaryPhoto', 'city', 'state', 'category', 'user'])->find($this->activeListingId) : null;
    }

    /**
     * Messages timeline for current active conversation.
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
        $this->showMobileChat = true;
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
            $isImage = str_starts_with($mime, 'image/');

            $storedPath = $this->attachment->store('attachments', 'public');
            $attachmentPath = $storedPath;
            $attachmentType = $isImage ? 'image' : 'file';
            $attachmentName = $originalName;
            $attachmentSize = $size;
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

        $isCurrentlyStarred = ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->where('is_starred', true)
        ->exists();

        ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->update(['is_starred' => !$isCurrentlyStarred]);
    }

    /**
     * Toggle Pin conversation.
     */
    public function togglePinConversation(): void
    {
        $authId = Auth::id();
        if (!$this->activePeerId) return;

        $isCurrentlyPinned = ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->where('is_pinned', true)
        ->exists();

        ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->update(['is_pinned' => !$isCurrentlyPinned]);
    }

    /**
     * Toggle Archive conversation.
     */
    public function toggleArchiveConversation(): void
    {
        $authId = Auth::id();
        if (!$this->activePeerId) return;

        $isArchived = ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->where('is_archived', true)
        ->exists();

        ContactMessage::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->where('recipient_id', $this->activePeerId)
              ->orWhere('sender_id', $this->activePeerId)->where('recipient_id', $authId);
        })
        ->when($this->activeListingId, fn($q) => $q->where('listing_id', $this->activeListingId))
        ->update(['is_archived' => !$isArchived]);
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
        $this->showMobileChat = false;
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
                'reported_user_id' => $this->activePeerId,
                'listing_id' => $this->activeListingId,
                'reason' => $this->reportReason,
                'description' => $this->reportNotes,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Report fallback
        }

        $this->reportModal = false;
        $this->reportNotes = '';
        session()->flash('messenger_toast', 'Report submitted to Trust & Safety team.');
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
                ['user_id' => $authId, 'blocked_user_id' => $this->activePeerId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        } catch (\Throwable $e) {
            // Block fallback
        }

        $this->blockConfirmModal = false;
        session()->flash('messenger_toast', 'User has been blocked.');
    }

    /**
     * Toggle right panel info.
     */
    public function toggleRightPanel(): void
    {
        $this->showRightPanel = !$this->showRightPanel;
    }

    /**
     * Return to mobile conversation list.
     */
    public function closeMobileChat(): void
    {
        $this->showMobileChat = false;
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

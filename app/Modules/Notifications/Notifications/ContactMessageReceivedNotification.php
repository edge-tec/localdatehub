<?php

namespace App\Modules\Notifications\Notifications;

use App\Modules\Contact\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ContactMessage $contactMessage
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $listing = $this->contactMessage->listing;

        return (new MailMessage)
            ->subject('You have received a new inquiry on LocalDateHub')
            ->greeting("Hello {$notifiable->name},")
            ->line("You received a secure message regarding your listing: \"{$listing?->title}\".")
            ->line('For your security, contact messages are encrypted end-to-end and screened for safety.')
            ->action('Read Message in Private Inbox', route('account.messages.index'))
            ->line('Never share passwords, banking details, or identity credentials with unknown parties.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contact_received',
            'message_id' => $this->contactMessage->id,
            'listing_id' => $this->contactMessage->listing_id,
            'sender_alias' => $this->contactMessage->sender_name ?: 'Anonymous Inquirer',
        ];
    }
}
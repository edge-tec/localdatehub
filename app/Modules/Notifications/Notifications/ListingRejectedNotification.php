<?php

namespace App\Modules\Notifications\Notifications;

use App\Modules\Listings\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Listing $listing,
        public readonly string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Important Notice Regarding Your Listing — LocalDateHub')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your listing \"{$this->listing->title}\" could not be approved due to community safety standards.")
            ->line("Reason: {$this->reason}")
            ->line('If you believe this determination was made in error, you may submit a formal appeal through your account settings.')
            ->action('Submit an Appeal', url('/account/appeal'))
            ->line('We appreciate your cooperation in keeping LocalDateHub safe and compliant.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'listing_rejected',
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'reason' => $this->reason,
            'message' => 'Your listing was rejected during moderation review.',
        ];
    }
}
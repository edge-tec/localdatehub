<?php

namespace App\Modules\Notifications\Notifications;

use App\Modules\Listings\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingRenewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Listing $listing
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Listing Successfully Renewed — LocalAdZone')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your listing \"{$this->listing->title}\" has been renewed for an additional 30 days.")
            ->line("Expires on: {$this->listing->expires_at?->format('F j, Y')}")
            ->action('View Live Listing', route('listings.show', $this->listing->slug));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'listing_renewed',
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
        ];
    }
}
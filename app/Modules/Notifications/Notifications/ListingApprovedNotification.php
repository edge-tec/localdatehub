<?php

namespace App\Modules\Notifications\Notifications;

use App\Modules\Listings\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingApprovedNotification extends Notification implements ShouldQueue
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
            ->subject('Good News! Your Listing is Live — LocalAdZone')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your listing \"{$this->listing->title}\" has passed Trust & Safety review and is now live on LocalAdZone.")
            ->line("Location: {$this->listing->city?->name}, {$this->listing->state?->code}")
            ->action('View Live Listing', route('listings.show', $this->listing->slug))
            ->line('You can promote or boost your listing anytime from your account dashboard.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'listing_approved',
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'url' => route('listings.show', $this->listing->slug),
            'message' => 'Your listing has been approved and published.',
        ];
    }
}
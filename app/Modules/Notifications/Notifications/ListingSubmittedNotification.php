<?php

namespace App\Modules\Notifications\Notifications;

use App\Modules\Listings\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingSubmittedNotification extends Notification implements ShouldQueue
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
            ->subject('Listing Submitted for Review — LocalAdZone')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your classified listing \"{$this->listing->title}\" has been submitted successfully.")
            ->line('Our automated safety and moderation filters are currently reviewing your listing. You will receive an update once it is live.')
            ->action('View My Listings', route('account.listings.index'))
            ->line('Thank you for advertising with LocalAdZone.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'listing_submitted',
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'message' => 'Listing submitted and queued for moderation review.',
        ];
    }
}
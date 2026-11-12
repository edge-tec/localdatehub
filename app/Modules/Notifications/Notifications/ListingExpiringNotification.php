<?php

namespace App\Modules\Notifications\Notifications;

use App\Modules\Listings\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Listing $listing,
        public readonly int $daysRemaining
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your Listing \"{$this->listing->title}\" is Expiring in {$this->daysRemaining} Days — LocalDateHub")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your listing \"{$this->listing->title}\" is scheduled to expire in {$this->daysRemaining} days.")
            ->line('You can renew your listing now with one click to keep receiving inquiries.')
            ->action('Renew Listing', route('account.listings.index'))
            ->line('Renewal is free for standard listings.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'listing_expiring',
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
<?php

namespace App\Modules\Notifications\Notifications;

use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PaymentTransaction $transaction,
        public readonly ?Invoice $invoice = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = '$' . number_format($this->transaction->amount_cents / 100, 2);
        $plan = $this->transaction->plan?->name ?: 'Listing Service / Promotion';

        $mail = (new MailMessage)
            ->subject('Receipt for Your LocalDateHub Purchase')
            ->greeting("Hello {$notifiable->name},")
            ->line("Thank you for your purchase. We have received your payment of {$amount} for {$plan}.")
            ->line("Transaction Reference: {$this->transaction->reference_id}");

        if ($this->invoice) {
            $mail->line("Invoice Number: {$this->invoice->invoice_number}")
                ->action('View & Download Invoice', route('account.invoices.show', $this->invoice));
        }

        return $mail->line('Thank you for choosing LocalDateHub.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_receipt',
            'transaction_id' => $this->transaction->id,
            'amount_cents' => $this->transaction->amount_cents,
            'reference_id' => $this->transaction->reference_id,
            'invoice_id' => $this->invoice?->id,
        ];
    }
}
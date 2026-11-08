<?php

namespace App\Modules\Billing\Services;

use App\Models\User;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\PaymentTransaction;
use Illuminate\Support\Str;

class InvoiceService
{
    /**
     * Create an invoice from a payment transaction.
     */
    public function createFromTransaction(PaymentTransaction $transaction, array $customLineItems = []): Invoice
    {
        $user = $transaction->user;
        $plan = $transaction->plan;
        $listing = $transaction->listing;

        $invoiceNumber = $this->generateInvoiceNumber();

        $lineItems = $customLineItems;
        if (empty($lineItems)) {
            $description = $plan ? $plan->name : 'Listing Service & Promotion';
            if ($listing) {
                $description .= " (Listing: #{$listing->id} - {$listing->title})";
            }

            $lineItems[] = [
                'name' => $plan ? $plan->name : 'Classified Advertisement Upgrade',
                'description' => $description,
                'quantity' => 1,
                'unit_price_cents' => $transaction->amount_cents,
                'total_cents' => $transaction->amount_cents,
            ];
        }

        $subtotal = $transaction->amount_cents;
        $tax = 0; // Adult classified listings generally zero-tax or inclusive
        $total = $subtotal + $tax;

        return Invoice::create([
            'user_id' => $user->id,
            'payment_transaction_id' => $transaction->id,
            'invoice_number' => $invoiceNumber,
            'status' => $transaction->status === 'completed' ? 'paid' : ($transaction->status === 'refunded' ? 'refunded' : 'unpaid'),
            'subtotal_cents' => $subtotal,
            'tax_cents' => $tax,
            'total_cents' => $total,
            'currency' => $transaction->currency ?: 'USD',
            'customer_name' => $user->display_name ?: $user->name ?: 'Valued Customer',
            'customer_email' => $user->email,
            'billing_address' => [
                'country' => 'United States',
                'state' => $user->state?->name ?? 'US',
                'city' => $user->city?->name ?? 'Local Area',
            ],
            'line_items' => $lineItems,
            'paid_at' => $transaction->status === 'completed' ? now() : null,
            'due_at' => now(),
        ]);
    }

    /**
     * Generate sequential/unique invoice number.
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ym') . '-';
        $random = strtoupper(Str::random(5));
        $count = Invoice::whereYear('created_at', date('Y'))->count() + 1;

        return sprintf('%s%05d-%s', $prefix, $count, $random);
    }
}
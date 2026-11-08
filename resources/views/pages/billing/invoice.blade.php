<x-layouts.app :title="'Invoice #' . $invoice->invoice_number . ' — LocalAdZone'">
<div class="py-12 bg-white min-h-screen text-slate-800 print:bg-white print:text-black print:py-0">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Action Bar (Hidden on print) -->
        <div class="mb-6 flex items-center justify-between print:hidden">
            <a href="{{ route('account.dashboard', ['tab' => 'billing']) }}" class="inline-flex items-center text-sm font-medium text-slate-400 hover:text-slate-900 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Billing Ledger
            </a>
            <div class="flex items-center space-x-3">
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-slate-200 rounded-lg shadow-sm text-sm font-medium text-slate-900 bg-slate-100 hover:bg-slate-200 transition-colors">
                    <svg class="w-4 h-4 mr-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print / Download PDF
                </button>
            </div>
        </div>

        <!-- Invoice Paper Container -->
        <div class="bg-slate-100 border border-slate-200 rounded-2xl p-8 sm:p-12 shadow-2xl relative overflow-hidden print:border-none print:shadow-none print:bg-white print:p-0">
            
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between pb-8 border-b border-slate-200 print:border-gray-200">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl font-black tracking-tight text-slate-900 print:text-black">Local<span class="text-rose-500">Ad</span>Zone</span>
                        <span class="text-xs uppercase tracking-wider bg-rose-50 text-rose-600 border border-rose-200 px-2 py-0.5 rounded font-mono">Invoice</span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400 print:text-gray-600 max-w-sm">
                        {{ $remit['company_name'] }}<br>
                        {{ $remit['address'] }}<br>
                        {{ $remit['city'] }}, {{ $remit['state'] }} {{ $remit['zip'] }}<br>
                        {{ $remit['country'] }}<br>
                        Support: {{ $remit['support_email'] }}
                    </p>
                </div>

                <div class="mt-6 sm:mt-0 text-left sm:text-right">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 print:text-gray-500">Invoice Number</span>
                    <h1 class="text-xl font-mono font-bold text-slate-900 print:text-black mt-0.5">{{ $invoice->invoice_number }}</h1>
                    
                    <div class="mt-3 flex items-center sm:justify-end space-x-2">
                        @if($invoice->status === 'paid')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 print:border-emerald-600 print:text-emerald-700">
                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-700" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Paid in Full
                            </span>
                        @elseif($invoice->status === 'refunded')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                Refunded
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-200">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Customer & Date Meta Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 py-8 border-b border-slate-200 print:border-gray-200">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 print:text-gray-500">Billed To</span>
                    <h3 class="text-sm font-semibold text-slate-900 print:text-black mt-1">{{ $invoice->customer_name }}</h3>
                    <p class="text-xs text-slate-400 print:text-gray-600 mt-0.5">
                        {{ $invoice->customer_email }}<br>
                        Account ID: #{{ $invoice->user_id }}<br>
                        @if($invoice->billing_address)
                            {{ $invoice->billing_address['city'] ?? '' }}, {{ $invoice->billing_address['state'] ?? '' }} {{ $invoice->billing_address['country'] ?? '' }}
                        @endif
                    </p>
                </div>

                <div class="sm:text-right">
                    <div class="space-y-1 text-xs text-slate-400 print:text-gray-600">
                        <div>
                            <span class="font-medium text-slate-600 print:text-gray-700">Invoice Date:</span>
                            {{ $invoice->created_at->format('M d, Y') }}
                        </div>
                        @if($invoice->paid_at)
                        <div>
                            <span class="font-medium text-slate-600 print:text-gray-700">Paid At:</span>
                            {{ $invoice->paid_at->format('M d, Y H:i T') }}
                        </div>
                        @endif
                        @if($invoice->transaction)
                        <div>
                            <span class="font-medium text-slate-600 print:text-gray-700">Payment Reference:</span>
                            <span class="font-mono text-slate-800 print:text-black">{{ $invoice->transaction->reference_id }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-slate-600 print:text-gray-700">Card / Method:</span>
                            {{ $invoice->transaction->card_brand ?? 'Card' }} ****{{ $invoice->transaction->card_last_four ?? '4242' }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="py-8">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-400 print:border-gray-300 print:text-gray-600">
                            <th class="pb-3 font-semibold uppercase tracking-wider">Description</th>
                            <th class="pb-3 text-center font-semibold uppercase tracking-wider w-20">Qty</th>
                            <th class="pb-3 text-right font-semibold uppercase tracking-wider w-28">Unit Price</th>
                            <th class="pb-3 text-right font-semibold uppercase tracking-wider w-28">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/40 print:divide-gray-200">
                        @foreach($invoice->line_items as $item)
                        <tr>
                            <td class="py-4">
                                <p class="font-semibold text-slate-900 print:text-black text-sm">{{ $item['name'] ?? 'Classified Service' }}</p>
                                <p class="text-slate-400 print:text-gray-600 mt-0.5">{{ $item['description'] ?? '' }}</p>
                            </td>
                            <td class="py-4 text-center text-slate-600 print:text-gray-700 font-mono">
                                {{ $item['quantity'] ?? 1 }}
                            </td>
                            <td class="py-4 text-right text-slate-600 print:text-gray-700 font-mono">
                                ${{ number_format(($item['unit_price_cents'] ?? 0) / 100, 2) }}
                            </td>
                            <td class="py-4 text-right font-bold text-slate-900 print:text-black font-mono">
                                ${{ number_format(($item['total_cents'] ?? 0) / 100, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals Calculation -->
            <div class="pt-6 border-t border-slate-200 print:border-gray-200 flex justify-end">
                <div class="w-full sm:w-64 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-400 print:text-gray-600">
                        <span>Subtotal:</span>
                        <span class="font-mono font-medium text-slate-800 print:text-black">{{ $invoice->formatted_subtotal }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400 print:text-gray-600">
                        <span>Tax / VAT (0%):</span>
                        <span class="font-mono font-medium text-slate-800 print:text-black">{{ $invoice->formatted_tax }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold text-slate-900 print:text-black pt-3 border-t border-slate-200 print:border-gray-200">
                        <span>Total Paid:</span>
                        <span class="font-mono text-base text-rose-600 print:text-black">{{ $invoice->formatted_total }} {{ $invoice->currency }}</span>
                    </div>
                </div>
            </div>

            <!-- Legal Disclaimers & Footer -->
            <div class="mt-12 pt-8 border-t border-slate-200 text-center text-[11px] text-slate-400 print:border-gray-200 print:text-gray-500">
                <p>Thank you for using LocalAdZone. This receipt serves as your official electronic invoice.</p>
                <p class="mt-1">Classified listings are subject to platform terms and Trust & Safety regulations. All fees are non-refundable once promotions commence, except as provided by law.</p>
            </div>

        </div>

    </div>
</div>
</x-layouts.app>
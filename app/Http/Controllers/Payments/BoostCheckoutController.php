<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Modules\Listings\Models\Listing;
use App\Modules\Payments\Crypto\Models\BoostPlan;
use App\Modules\Payments\Crypto\Models\CryptoPayment;
use App\Modules\Payments\Crypto\Models\PaymentGateway;
use App\Modules\Payments\Crypto\Services\CryptoPaymentService;
use App\Modules\Payments\Crypto\Support\CryptoCurrencyRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoostCheckoutController extends Controller
{
    public function __construct(
        protected CryptoPaymentService $paymentService
    ) {}

    /**
     * Show available Boost Plans and crypto payment options for a listing.
     */
    public function show(Listing $listing): View
    {
        $this->authorizeListingOwner($listing);

        $plans = BoostPlan::active()->get();
        $gateways = PaymentGateway::where('is_enabled', true)->where('provider', 'inhouse')->get();
        if ($gateways->isEmpty()) {
            $gateways = PaymentGateway::where('provider', 'inhouse')->get();
        }
        $currencies = CryptoCurrencyRegistry::getSupportedCurrencies();
        $networks = CryptoCurrencyRegistry::getSupportedNetworks();

        return view('boost.checkout', [
            'listing' => $listing,
            'plans' => $plans,
            'gateways' => $gateways,
            'currencies' => $currencies,
            'networks' => $networks,
            'activeBoost' => $listing->activeBoost,
        ]);
    }

    /**
     * Initialize a crypto payment invoice for boosting a listing.
     */
    public function createPayment(Request $request, Listing $listing): JsonResponse|RedirectResponse
    {
        $this->authorizeListingOwner($listing);

        $validated = $request->validate([
            'boost_plan_id' => ['required', 'exists:boost_plans,id'],
            'gateway_id' => ['required', 'exists:payment_gateways,id'],
            'currency' => ['required', 'string', 'max:10'],
            'network' => ['required', 'string', 'max:20'],
        ]);

        $plan = BoostPlan::where('is_active', true)->findOrFail($validated['boost_plan_id']);
        $gateway = PaymentGateway::where('is_enabled', true)->findOrFail($validated['gateway_id']);

        $currency = strtoupper($validated['currency']);
        $network = strtoupper($validated['network']);

        // Verify currency and network compatibility
        $allowedNetworks = CryptoCurrencyRegistry::getNetworksForCurrency($currency);
        if (!isset($allowedNetworks[$network])) {
            return back()->withErrors(['network' => "Network {$network} is not supported for currency {$currency}."]);
        }

        $payment = $this->paymentService->createBoostCheckout(
            user: auth()->user(),
            listing: $listing,
            plan: $plan,
            gateway: $gateway,
            currency: $currency,
            network: $network
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'payment' => $payment,
                'invoice_url' => route('boost.invoice', $payment->internal_order_id),
            ]);
        }

        return redirect()->route('boost.invoice', $payment->internal_order_id);
    }

    /**
     * Display the cryptocurrency invoice screen.
     */
    public function showInvoice(string $orderId): View
    {
        $payment = CryptoPayment::where('internal_order_id', $orderId)
            ->with(['listing', 'boostPlan', 'gateway', 'boost'])
            ->firstOrFail();

        // Ensure user can only view their own invoice
        if ($payment->user_id !== auth()->id() && !auth()->user()?->hasRole('admin')) {
            abort(403, 'Unauthorized access to payment invoice.');
        }

        return view('boost.invoice', [
            'payment' => $payment,
            'listing' => $payment->listing,
            'plan' => $payment->boostPlan,
            'gateway' => $payment->gateway,
        ]);
    }

    /**
     * Live JSON endpoint for invoice status polling / real-time updates.
     */
    public function checkStatus(string $orderId): JsonResponse
    {
        $payment = CryptoPayment::where('internal_order_id', $orderId)->firstOrFail();

        if ($payment->user_id !== auth()->id() && !auth()->user()?->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Sync with provider if still awaiting or confirming
        if ($payment->isPending()) {
            $payment = $this->paymentService->syncPaymentStatus($payment);
        }

        return response()->json([
            'status' => $payment->status,
            'is_completed' => $payment->isCompleted(),
            'is_expired' => $payment->isExpired(),
            'confirmations' => $payment->confirmations,
            'required_confirmations' => $payment->required_confirmations,
            'received_amount' => $payment->received_amount,
            'transaction_hash' => $payment->transaction_hash,
            'redirect_url' => $payment->isCompleted() ? route('account.listings.index', ['boost_success' => 1]) : null,
        ]);
    }

    /**
     * User payment history view.
     */
    public function userHistory(): View
    {
        $payments = CryptoPayment::where('user_id', auth()->id())
            ->with(['listing', 'boostPlan', 'gateway', 'boost'])
            ->latest()
            ->paginate(15);

        return view('account.payments.index', [
            'payments' => $payments,
        ]);
    }

    protected function authorizeListingOwner(Listing $listing): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        if ($listing->user_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'You do not have permission to boost this listing.');
        }
    }
}


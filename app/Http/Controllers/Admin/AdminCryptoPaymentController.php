<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\AdminAuditService;
use App\Modules\Payments\Crypto\Gateways\CryptoGatewayManager;
use App\Modules\Payments\Crypto\Models\BoostPlan;
use App\Modules\Payments\Crypto\Models\CryptoPayment;
use App\Modules\Payments\Crypto\Models\PaymentGateway;
use App\Modules\Payments\Crypto\Models\PaymentWebhookEvent;
use App\Modules\Payments\Crypto\Services\CryptoPaymentService;
use App\Modules\Payments\Crypto\Services\ListingBoostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCryptoPaymentController extends Controller
{
    public function __construct(
        protected CryptoGatewayManager $gatewayManager,
        protected CryptoPaymentService $paymentService,
        protected ListingBoostService $boostService
    ) {}

    /**
     * Display Crypto Payments Index.
     */
    public function indexPayments(Request $request): View
    {
        $query = CryptoPayment::with(['user', 'listing', 'boostPlan', 'gateway', 'boost']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('gateway_id')) {
            $query->where('gateway_id', $request->input('gateway_id'));
        }

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('internal_order_id', 'like', "%{$s}%")
                  ->orWhere('provider_payment_id', 'like', "%{$s}%")
                  ->orWhere('transaction_hash', 'like', "%{$s}%")
                  ->orWhere('payment_address', 'like', "%{$s}%")
                  ->orWhereHas('user', fn ($uq) => $uq->where('email', 'like', "%{$s}%")->orWhere('username', 'like', "%{$s}%"))
                  ->orWhereHas('listing', fn ($lq) => $lq->where('title', 'like', "%{$s}%"));
            });
        }

        $payments = $query->latest()->paginate(20)->withQueryString();
        $gateways = PaymentGateway::orderBy('name')->get();

        $stats = [
            'total_volume_usd' => CryptoPayment::where('status', CryptoPayment::STATUS_COMPLETED)->sum('fiat_amount'),
            'completed_count' => CryptoPayment::where('status', CryptoPayment::STATUS_COMPLETED)->count(),
            'pending_count' => CryptoPayment::whereIn('status', [CryptoPayment::STATUS_AWAITING_PAYMENT, CryptoPayment::STATUS_CONFIRMING, CryptoPayment::STATUS_PENDING])->count(),
            'underpaid_count' => CryptoPayment::where('status', CryptoPayment::STATUS_UNDERPAID)->count(),
        ];

        return view('admin.crypto.payments.index', [
            'payments' => $payments,
            'gateways' => $gateways,
            'stats' => $stats,
        ]);
    }

    /**
     * Show Payment Details.
     */
    public function showPayment(CryptoPayment $payment): View
    {
        $payment->load(['user', 'listing', 'boostPlan', 'gateway', 'boost', 'webhookEvents']);

        return view('admin.crypto.payments.show', [
            'payment' => $payment,
            'webhookEvents' => $payment->webhookEvents()->latest()->get(),
        ]);
    }

    /**
     * Manually sync payment status with provider.
     */
    public function syncPayment(CryptoPayment $payment): RedirectResponse
    {
        $before = $payment->status;
        $payment = $this->paymentService->syncPaymentStatus($payment);

        AdminAuditService::log(
            action: 'crypto_payment.admin_sync',
            target: $payment,
            before: ['status' => $before],
            after: ['status' => $payment->status]
        );

        return back()->with('success', "Payment status checked: {$before} -> {$payment->status}");
    }

    /**
     * Display Payment Gateways List.
     */
    public function indexGateways(): View
    {
        $gateways = PaymentGateway::where('provider', 'inhouse')
            ->orderBy('display_order')
            ->get();

        return view('admin.crypto.gateways.index', [
            'gateways' => $gateways,
        ]);
    }

    /**
     * Edit Gateway Settings.
     */
    public function editGateway(PaymentGateway $gateway): View
    {
        $credentials = $gateway->getCredentials();
        $webhookSecret = $gateway->getWebhookSecret();

        return view('admin.crypto.gateways.edit', [
            'gateway' => $gateway,
            'credentials' => $credentials,
            'webhookSecret' => $webhookSecret,
        ]);
    }

    /**
     * Update Gateway Settings.
     */
    public function updateGateway(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'environment' => ['required', 'in:sandbox,live'],
            'is_enabled' => ['required', 'boolean'],
            'credentials' => ['nullable', 'array'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'display_order' => ['required', 'integer', 'min:0'],
        ]);

        $gateway->name = $validated['name'];
        $gateway->environment = $validated['environment'];
        $gateway->is_enabled = (bool) $validated['is_enabled'];
        $gateway->display_order = (int) $validated['display_order'];

        if (!empty($validated['credentials'])) {
            // Merge with existing credentials so empty fields don't overwrite if left blank
            $existing = $gateway->getCredentials();
            $merged = array_merge($existing, array_filter($validated['credentials'], fn ($val) => !is_null($val) && $val !== ''));
            $gateway->setCredentials($merged);
        }

        if ($request->has('webhook_secret')) {
            $gateway->setWebhookSecret($validated['webhook_secret']);
        }

        $gateway->save();

        AdminAuditService::log(
            action: 'payment_gateway.updated',
            target: $gateway,
            before: [],
            after: ['is_enabled' => $gateway->is_enabled, 'environment' => $gateway->environment]
        );

        return redirect()->route('admin.crypto.gateways.index')->with('success', "Payment gateway {$gateway->name} updated successfully.");
    }

    /**
     * Test Gateway API Connection.
     */
    public function testGateway(PaymentGateway $gateway): JsonResponse|RedirectResponse
    {
        $adapter = $this->gatewayManager->get($gateway);
        $result = $adapter->testConnection($gateway);

        $gateway->update([
            'last_tested_at' => now(),
            'last_test_status' => $result->success ? 'success' : 'failed',
            'last_test_message' => $result->message,
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => $result->success,
                'message' => $result->message,
                'details' => $result->details,
            ]);
        }

        $type = $result->success ? 'success' : 'error';
        return back()->with($type, $result->message);
    }

    /**
     * Display Boost Plans Management.
     */
    public function indexPlans(): View
    {
        $plans = BoostPlan::withCount(['boosts', 'payments'])->orderBy('display_order')->get();

        return view('admin.crypto.plans.index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Store new Boost Plan.
     */
    public function storePlan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'featured_position' => ['required', 'string', 'max:60'],
            'priority_level' => ['required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['required', 'boolean'],
            'display_order' => ['required', 'integer', 'min:0'],
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(4);

        $plan = BoostPlan::create($validated);

        AdminAuditService::log(action: 'boost_plan.created', target: $plan);

        return back()->with('success', "Boost plan {$plan->name} created successfully.");
    }

    /**
     * Update existing Boost Plan.
     */
    public function updatePlan(Request $request, BoostPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'featured_position' => ['required', 'string', 'max:60'],
            'priority_level' => ['required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['required', 'boolean'],
            'display_order' => ['required', 'integer', 'min:0'],
        ]);

        $before = $plan->toArray();
        $plan->update($validated);

        AdminAuditService::log(action: 'boost_plan.updated', target: $plan, before: $before, after: $plan->toArray());

        return back()->with('success', "Boost plan {$plan->name} updated successfully.");
    }

    /**
     * Delete Boost Plan if unused.
     */
    public function destroyPlan(BoostPlan $plan): RedirectResponse
    {
        if ($plan->payments()->exists() || $plan->boosts()->exists()) {
            return back()->with('error', "Cannot delete plan {$plan->name} because it has existing transaction records. You can disable it instead.");
        }

        $plan->delete();
        AdminAuditService::log(action: 'boost_plan.deleted', target: $plan);

        return back()->with('success', "Boost plan {$plan->name} removed.");
    }
}


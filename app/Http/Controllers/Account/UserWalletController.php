<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Modules\Monetization\Models\CreditPackage;
use App\Modules\Monetization\Services\PostingQuotaService;
use App\Modules\Monetization\Services\UserWalletService;
use App\Modules\Payments\Crypto\Gateways\CryptoGatewayManager;
use App\Modules\Payments\Crypto\Models\CryptoCurrency;
use App\Modules\Payments\Crypto\Models\CryptoNetwork;
use App\Modules\Payments\Crypto\Models\CryptoPayment;
use App\Modules\Payments\Crypto\Models\CryptoPaymentInvoice;
use App\Modules\Payments\Crypto\Models\PaymentGateway;
use App\Modules\Payments\Crypto\Services\CryptoPaymentService;
use App\Modules\Payments\Crypto\Services\InHouse\CryptoInvoiceService;
use App\Modules\Payments\Crypto\Support\CryptoCurrencyRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserWalletController extends Controller
{
    public function __construct(
        protected UserWalletService $walletService,
        protected PostingQuotaService $quotaService,
        protected CryptoGatewayManager $gatewayManager,
        protected CryptoPaymentService $cryptoPaymentService,
        protected CryptoInvoiceService $inhouseInvoiceService
    ) {}

    /**
     * User Wallet Overview.
     */
    public function index()
    {
        $user = Auth::user();
        $wallet = $this->walletService->getOrCreateWallet($user);
        $transactions = $user->walletTransactions()
            ->with(['listing', 'cryptoPayment'])
            ->latest()
            ->paginate(15);

        $packages = CreditPackage::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $quotaSummary = $this->quotaService->getUserQuotaSummary($user);

        return view('account.wallet.index', [
            'user' => $user,
            'wallet' => $wallet,
            'transactions' => $transactions,
            'packages' => $packages,
            'quotaSummary' => $quotaSummary,
        ]);
    }

    /**
     * Checkout page for purchasing a credit package.
     */
    public function checkout(CreditPackage $package)
    {
        abort_unless($package->is_active, 404);

        $gateways = PaymentGateway::where('is_enabled', true)
            ->where('provider', 'inhouse')
            ->orderBy('display_order')
            ->get();

        if ($gateways->isEmpty()) {
            $gateways = PaymentGateway::where('provider', 'inhouse')->get();
        }

        $supportedCurrencies = CryptoCurrencyRegistry::getSupportedCurrencies();

        $pricingService = app(\App\Modules\Payments\Crypto\Services\InHouse\CryptoPricingService::class);
        $estimates = [];
        foreach (array_keys($supportedCurrencies) as $symbol) {
            try {
                $estimates[$symbol] = $pricingService->convertUsdToCrypto((float) $package->price_usd, $symbol);
            } catch (\Throwable $e) {
                $estimates[$symbol] = (string) round((float) $package->price_usd, 4);
            }
        }

        return view('account.wallet.checkout', [
            'package' => $package,
            'gateways' => $gateways,
            'currencies' => $supportedCurrencies,
            'estimates' => $estimates,
        ]);
    }

    /**
     * Initiate crypto payment for a credit package.
     */
    public function storePayment(Request $request, CreditPackage $package)
    {
        abort_unless($package->is_active, 404);

        $validated = $request->validate([
            'gateway_id' => 'required|exists:payment_gateways,id',
            'currency' => 'required|string',
            'network' => 'required|string',
        ]);

        $gateway = PaymentGateway::where('id', $validated['gateway_id'])
            ->where('is_enabled', true)
            ->firstOrFail();

        $currency = strtoupper($validated['currency']);
        $network = strtoupper($validated['network']);

        $user = Auth::user();

        // Create genuine in-house cryptocurrency invoice with derived deposit address
        try {
            $inhouseInvoice = $this->inhouseInvoiceService->createInvoice(
                $user,
                (float) $package->price_usd,
                $currency,
                $network,
                $package->id,
                null,
                [
                    'package_name' => $package->name,
                    'credits' => $package->credits,
                    'bonus_credits' => $package->bonus_credits,
                ]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to generate in-house crypto invoice for package [{$package->id}]: " . $e->getMessage());
            return back()->withErrors(['network' => "Unable to generate a secure blockchain deposit address for {$currency} on {$network}. Please verify network settings or try another currency."]);
        }

        $depositAddress = $inhouseInvoice->depositAddress?->public_address;
        if (empty($depositAddress)) {
            return back()->withErrors(['network' => "Deposit address generation failed for {$network}. Please choose another network or contact support."]);
        }

        $expectedAmount = (string) $inhouseInvoice->expected_crypto_amount;
        $orderId = $inhouseInvoice->invoice_number;

        // Create CryptoPayment record
        $payment = CryptoPayment::create([
            'user_id' => $user->id,
            'payment_type' => 'credit_package',
            'credit_package_id' => $package->id,
            'gateway_id' => $gateway->id,
            'internal_order_id' => $orderId,
            'payment_currency' => $currency,
            'payment_network' => $network,
            'expected_amount' => $expectedAmount,
            'fiat_amount' => $package->price_usd,
            'fiat_currency' => 'USD',
            'status' => CryptoPayment::STATUS_AWAITING_PAYMENT,
            'payment_address' => $depositAddress,
            'qr_code_data' => $depositAddress,
            'expires_at' => now()->addMinutes(30),
            'metadata' => [
                'package_name' => $package->name,
                'credits' => $package->credits,
                'bonus_credits' => $package->bonus_credits,
                'user_email' => $user->email,
                'inhouse_invoice_id' => $inhouseInvoice?->id,
            ],
        ]);

        return redirect()->route('account.wallet.invoice', $payment);
    }

    /**
     * Show interactive payment invoice.
     */
    public function invoice(CryptoPayment $payment)
    {
        abort_unless($payment->user_id === Auth::id(), 403);

        return view('account.wallet.invoice', [
            'payment' => $payment->load(['creditPackage', 'gateway']),
        ]);
    }

    /**
     * Live polling status for wallet payment invoice.
     */
    public function status(CryptoPayment $payment): JsonResponse
    {
        abort_unless($payment->user_id === Auth::id(), 403);

        if (!$payment->isCompleted() && !$payment->isExpired()) {
            try {
                $payment = $this->cryptoPaymentService->syncPaymentStatus($payment);
            } catch (\Throwable $e) {
                // Continue with current payment state if node check throttles
            }
        }

        $wallet = $this->walletService->getOrCreateWallet($payment->user);

        return response()->json([
            'status' => $payment->status,
            'is_completed' => $payment->isCompleted(),
            'is_expired' => $payment->isExpired(),
            'received_amount' => $payment->received_amount,
            'expected_amount' => $payment->expected_amount,
            'confirmations' => $payment->confirmations,
            'required_confirmations' => $payment->required_confirmations,
            'current_balance' => $wallet->balance,
            'redirect_url' => route('account.wallet.index'),
        ]);
    }
}


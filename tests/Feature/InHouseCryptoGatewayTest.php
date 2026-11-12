<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\Permission;
use App\Modules\Admin\Models\Role;
use App\Modules\Monetization\Models\UserWallet;
use App\Modules\Payments\Crypto\Models\CryptoCurrency;
use App\Modules\Payments\Crypto\Models\CryptoExchangeRate;
use App\Modules\Payments\Crypto\Models\CryptoGasReserve;
use App\Modules\Payments\Crypto\Models\CryptoNetwork;
use App\Modules\Payments\Crypto\Models\CryptoPaymentInvoice;
use App\Modules\Payments\Crypto\Models\CryptoPaymentSetting;
use App\Modules\Payments\Crypto\Models\CryptoSweepApproval;
use App\Modules\Payments\Crypto\Models\CryptoSweepTransaction;
use App\Modules\Payments\Crypto\Models\CryptoTreasuryWallet;
use App\Modules\Payments\Crypto\Services\InHouse\CryptoInvoiceService;
use App\Modules\Payments\Crypto\Services\InHouse\CryptoPricingService;
use App\Modules\Payments\Crypto\Services\InHouse\DepositAddressService;
use App\Modules\Payments\Crypto\Services\InHouse\GasReserveService;
use App\Modules\Payments\Crypto\Services\InHouse\HdWalletService;
use App\Modules\Payments\Crypto\Services\InHouse\HybridTreasuryEngine;
use App\Modules\Payments\Crypto\Services\InHouse\PaymentVerificationService;
use App\Modules\Payments\Crypto\Services\InHouse\SweepCostCalculatorService;
use App\Modules\Payments\Crypto\Services\InHouse\TreasuryWalletService;
use App\Modules\Payments\Crypto\Services\InHouse\UserBalanceCreditService;
use Database\Seeders\InHouseCryptoGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InHouseCryptoGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InHouseCryptoGatewaySeeder::class);

        $this->user = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $role = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'is_system' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage Plans & Billing',
            'slug' => 'billing.plans',
            'module' => 'billing',
        ]);

        $role->permissions()->attach($permission->id);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'is_age_verified' => true,
        ]);

        $this->admin->roles()->attach($role->id);
    }

    public function test_hd_wallet_generates_unique_deterministic_addresses_for_all_chains()
    {
        $hd = app(HdWalletService::class);
        $trcNetwork = CryptoNetwork::where('network_key', 'TRC20')->firstOrFail();
        $ercNetwork = CryptoNetwork::where('network_key', 'ERC20')->firstOrFail();
        $btcNetwork = CryptoNetwork::where('network_key', 'BTC')->firstOrFail();

        $addrTron1 = $hd->derivePublicAddress($trcNetwork, 1);
        $addrTron2 = $hd->derivePublicAddress($trcNetwork, 2);
        $addrEvm = $hd->derivePublicAddress($ercNetwork, 1);
        $addrBtc = $hd->derivePublicAddress($btcNetwork, 1);

        // Assert TRON format (starts with T, 34 chars)
        $this->assertStringStartsWith('T', $addrTron1);
        $this->assertEquals(34, strlen($addrTron1));
        $this->assertNotEquals($addrTron1, $addrTron2);

        // Assert EVM format (starts with 0x, 42 chars)
        $this->assertStringStartsWith('0x', $addrEvm);
        $this->assertEquals(42, strlen($addrEvm));

        // Assert BTC SegWit format (starts with bc1q, 42 chars)
        $this->assertStringStartsWith('bc1q', $addrBtc);
        $this->assertEquals(42, strlen($addrBtc));
    }

    public function test_usd_pricing_engine_uses_decimal_safe_arithmetic()
    {
        $pricing = app(CryptoPricingService::class);

        // Pegged stablecoin: $50 USD -> exactly 50.000000 USDT
        $convUsdt = $pricing->convertUsdToCrypto(50.00, 'USDT');
        $this->assertEquals('50.000000', $convUsdt['crypto_amount']);

        // Volatile asset conversion: $100 USD at $88,500 BTC/USD
        $convBtc = $pricing->convertUsdToCrypto(100.00, 'BTC');
        $this->assertEquals(8, $convBtc['precision']);
        $this->assertGreaterThan(0, (float) $convBtc['crypto_amount']);
    }

    public function test_invoice_service_creates_unexpired_invoice_with_unique_deposit_address()
    {
        $invoiceService = app(CryptoInvoiceService::class);

        $invoice = $invoiceService->createInvoice(
            user: $this->user,
            usdAmount: 100.00,
            currencySymbol: 'USDT',
            networkKey: 'TRC20'
        );

        $this->assertNotNull($invoice);
        $this->assertStringStartsWith('CRYPTO-INV-', $invoice->invoice_number);
        $this->assertEquals(CryptoPaymentInvoice::STATUS_WAITING_FOR_PAYMENT, $invoice->status);
        $this->assertEquals('100.00', $invoice->fiat_amount_usd);
        $this->assertNotNull($invoice->depositAddress);
        $this->assertStringStartsWith('T', $invoice->depositAddress->public_address);
        $this->assertFalse($invoice->isExpired());
    }

    public function test_payment_verification_confirms_valid_blockchain_tx_and_credits_balance()
    {
        $invoiceService = app(CryptoInvoiceService::class);
        $verificationService = app(PaymentVerificationService::class);

        $invoice = $invoiceService->createInvoice(
            user: $this->user,
            usdAmount: 50.00,
            currencySymbol: 'USDT',
            networkKey: 'TRC20'
        );

        $txHash = 'TRON-TX-' . bin2hex(random_bytes(16));

        // Submit detected transaction meeting required confirmation count (1 on TRC20)
        $res = $verificationService->processDetectedTransaction(
            invoice: $invoice,
            txHash: $txHash,
            fromAddress: 'TSenderAddressDummy123456789012345',
            toAddress: $invoice->depositAddress->public_address,
            amount: $invoice->expected_crypto_amount,
            confirmations: 1,
            tokenContract: 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
        );

        $this->assertEquals('COMPLETED', $res['status']);
        $this->assertEquals(CryptoPaymentInvoice::STATUS_COMPLETED, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->credited_at);

        // Assert user wallet received balance credit
        $wallet = UserWallet::where('user_id', $this->user->id)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(50, $wallet->balance);

        // Assert immutable financial ledger record
        $this->assertDatabaseHas('crypto_internal_ledger', [
            'user_id' => $this->user->id,
            'invoice_id' => $invoice->id,
            'entry_type' => 'DEPOSIT_CREDIT',
            'amount_usd' => 50.00,
        ]);
    }

    public function test_payment_verification_detects_underpaid_transaction()
    {
        $invoiceService = app(CryptoInvoiceService::class);
        $verificationService = app(PaymentVerificationService::class);

        $invoice = $invoiceService->createInvoice(
            user: $this->user,
            usdAmount: 100.00,
            currencySymbol: 'USDT',
            networkKey: 'TRC20'
        );

        $txHash = 'TRON-UNDERPAID-TX-' . bin2hex(random_bytes(16));

        // Send 40 USDT instead of 100 USDT
        $res = $verificationService->processDetectedTransaction(
            invoice: $invoice,
            txHash: $txHash,
            fromAddress: 'TSenderAddressDummy123456789012345',
            toAddress: $invoice->depositAddress->public_address,
            amount: '40.000000',
            confirmations: 1,
            tokenContract: 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
        );

        $this->assertEquals('UNDERPAID', $res['status']);
        $this->assertEquals(CryptoPaymentInvoice::STATUS_UNDERPAID, $invoice->fresh()->status);
        // User balance must NOT be credited automatically for insufficient deposit
        $this->assertNull($invoice->fresh()->credited_at);
    }

    public function test_payment_verification_rejects_unapproved_token_contract()
    {
        $invoiceService = app(CryptoInvoiceService::class);
        $verificationService = app(PaymentVerificationService::class);

        $invoice = $invoiceService->createInvoice(
            user: $this->user,
            usdAmount: 50.00,
            currencySymbol: 'USDT',
            networkKey: 'TRC20'
        );

        $txHash = 'TRON-FAKE-TOKEN-TX-' . bin2hex(random_bytes(16));

        // Send payment with a fake token contract
        $res = $verificationService->processDetectedTransaction(
            invoice: $invoice,
            txHash: $txHash,
            fromAddress: 'TSenderDummy',
            toAddress: $invoice->depositAddress->public_address,
            amount: '50.000000',
            confirmations: 1,
            tokenContract: 'TFakeTokenContract1234567890123456'
        );

        $this->assertEquals('FAILED_VALIDATION', $res['status']);
        $this->assertEquals(CryptoPaymentInvoice::STATUS_MANUAL_REVIEW, $invoice->fresh()->status);
    }

    public function test_small_confirmed_deposit_sweeps_automatically_to_temporary_trust_wallet()
    {
        $invoiceService = app(CryptoInvoiceService::class);
        $verificationService = app(PaymentVerificationService::class);
        $treasuryEngine = app(HybridTreasuryEngine::class);

        // Configure Temporary Trust Wallet address
        $tempWallet = CryptoTreasuryWallet::where('tier', 'temporary_trust_wallet')
            ->where('network_key', 'TRC20')
            ->firstOrFail();

        $invoice = $invoiceService->createInvoice(
            user: $this->user,
            usdAmount: 50.00, // <= $100 threshold
            currencySymbol: 'USDT',
            networkKey: 'TRC20'
        );

        $txHash = 'SWEEP-TEST-TX-' . bin2hex(random_bytes(16));
        $verificationService->processDetectedTransaction(
            invoice: $invoice,
            txHash: $txHash,
            fromAddress: 'TSenderDummy',
            toAddress: $invoice->depositAddress->public_address,
            amount: '50.000000',
            confirmations: 1,
            tokenContract: 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
        );

        // Process sweep
        $sweep = $treasuryEngine->handleConfirmedInvoiceSweep($invoice->fresh());

        $this->assertNotNull($sweep);
        $this->assertEquals(CryptoSweepTransaction::TYPE_AUTOMATIC_SMALL, $sweep->sweep_type);
        $this->assertEquals(CryptoSweepTransaction::STATUS_CONFIRMED, $sweep->status);
        $this->assertEquals($tempWallet->public_receiving_address, $sweep->destination_address);
        $this->assertNotNull($sweep->sweep_tx_hash);
    }

    public function test_large_deposit_queues_for_admin_approval_and_executes_on_approval()
    {
        $invoiceService = app(CryptoInvoiceService::class);
        $verificationService = app(PaymentVerificationService::class);
        $treasuryEngine = app(HybridTreasuryEngine::class);

        $invoice = $invoiceService->createInvoice(
            user: $this->user,
            usdAmount: 500.00, // > $100 auto threshold
            currencySymbol: 'USDT',
            networkKey: 'TRC20'
        );

        $txHash = 'LARGE-SWEEP-TX-' . bin2hex(random_bytes(16));
        $verificationService->processDetectedTransaction(
            invoice: $invoice,
            txHash: $txHash,
            fromAddress: 'TSenderDummy',
            toAddress: $invoice->depositAddress->public_address,
            amount: '500.000000',
            confirmations: 1,
            tokenContract: 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
        );

        // User balance must be credited immediately regardless of sweep
        $this->assertEquals(500, UserWallet::where('user_id', $this->user->id)->first()->balance);

        // Trigger sweep evaluation -> queues for approval
        $sweep = $treasuryEngine->handleConfirmedInvoiceSweep($invoice->fresh());

        $this->assertNotNull($sweep);
        $this->assertEquals(CryptoSweepTransaction::TYPE_ADMIN_APPROVED_LARGE, $sweep->sweep_type);
        $this->assertEquals(CryptoSweepTransaction::STATUS_AWAITING_APPROVAL, $sweep->status);

        $approval = CryptoSweepApproval::where('sweep_transaction_id', $sweep->id)->first();
        $this->assertNotNull($approval);
        $this->assertEquals('PENDING', $approval->status);

        // Admin approves transfer
        $executedSweep = $treasuryEngine->processApproval($approval->id, $this->admin, true);

        $this->assertEquals(CryptoSweepTransaction::STATUS_CONFIRMED, $executedSweep->status);
        $this->assertEquals('APPROVED', $approval->fresh()->status);
        $this->assertEquals($this->admin->id, $approval->fresh()->admin_id);
    }

    public function test_emergency_pause_halts_all_treasury_sweeping_immediately()
    {
        $treasuryEngine = app(HybridTreasuryEngine::class);

        // Activate Emergency Pause
        $treasuryEngine->setEmergencyPause(true, 'Security incident drill');
        $this->assertTrue($treasuryEngine->isEmergencyPaused());

        $invoiceService = app(CryptoInvoiceService::class);
        $verificationService = app(PaymentVerificationService::class);

        $invoice = $invoiceService->createInvoice(
            user: $this->user,
            usdAmount: 25.00,
            currencySymbol: 'USDT',
            networkKey: 'TRC20'
        );

        $txHash = 'EMERGENCY-TX-' . bin2hex(random_bytes(16));
        // Payment verification continues
        $verificationService->processDetectedTransaction(
            invoice: $invoice,
            txHash: $txHash,
            fromAddress: 'TSenderDummy',
            toAddress: $invoice->depositAddress->public_address,
            amount: '25.000000',
            confirmations: 1,
            tokenContract: 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
        );

        $this->assertEquals(CryptoPaymentInvoice::STATUS_COMPLETED, $invoice->fresh()->status);
        $this->assertEquals(25, UserWallet::where('user_id', $this->user->id)->first()->balance);

        // But sweeping returns null (halted)
        $sweep = $treasuryEngine->handleConfirmedInvoiceSweep($invoice->fresh());
        $this->assertNull($sweep);
    }

    public function test_gas_reserve_service_enforces_emergency_reserve_protection()
    {
        $gasService = app(GasReserveService::class);

        // TRC20 current reserve is 500 TRX, emergency reserve is 30 TRX
        $this->assertTrue($gasService->canAffordGas('TRC20', '15.00000000'));

        // Fee of 490 TRX would leave 10 TRX (< 30 emergency reserve) -> must fail-closed
        $this->assertFalse($gasService->canAffordGas('TRC20', '490.00000000'));

        // Assert critical gas alert was created
        $this->assertDatabaseHas('crypto_gas_alerts', [
            'network_key' => 'TRC20',
            'alert_level' => 'CRITICAL',
        ]);
    }

    public function test_admin_can_manage_hybrid_treasury_tab_in_enterprise_hub()
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\AdminEnterpriseHub::class, ['initialTab' => 'payments'])
            ->call('setPaymentSubTab', 'treasury')
            ->assertSet('paymentSubTab', 'treasury')
            ->assertSee('Hybrid Treasury Fund Management')
            ->assertSee('Temporary Treasury (Trust Wallet)')
            ->assertSee('Main Treasury (Primary Trust Wallet)')
            ->assertSee('Gas Reserve Management &amp; Sweep Cost Engine', false)
            ->call('toggleTreasuryEmergencyPause')
            ->assertSee('EMERGENCY: All automated and queued treasury sweeps have been PAUSED')
            ->call('toggleTreasuryEmergencyPause') // resume
            ->set('treasurySettings.temp_trust_wallet_trc20', 'TLMYTVqBcN8CTtGVCvDhQ5Pue6zveknMva')
            ->set('treasurySettings.main_trust_wallet_trc20', 'TEpqggfDUnVw4i12cM2RYEgwhgaruhAmKo')
            ->call('saveTreasurySettings')
            ->assertSee('Hybrid Treasury thresholds and Trust Wallet addresses saved successfully')
            ->call('simulateTestDeposit', 'TRC20', 100.0)
            ->assertSee('Test Funds Loaded')
            ->set('manualSweepNetwork', 'TRC20')
            ->set('manualSweepCurrency', 'USDT')
            ->set('manualSweepAmount', '50.00')
            ->set('manualSweepDestination', 'TEpqggfDUnVw4i12cM2RYEgwhgaruhAmKo')
            ->call('executeManualConsolidation')
            ->assertSee('to Main Trust Wallet successfully');

        $this->assertDatabaseHas('crypto_sweep_transactions', [
            'network_key' => 'TRC20',
            'currency_symbol' => 'USDT',
            'amount' => '50',
            'sweep_type' => 'manual_admin',
        ]);
    }

    public function test_realtime_evm_bnb_scanner_detects_deposit_within_fee_tolerance_and_credits_wallet()
    {
        $invoiceService = app(CryptoInvoiceService::class);
        $scanner = app(\App\Modules\Payments\Crypto\Services\InHouse\BlockchainDepositScannerService::class);

        $invoice = $invoiceService->createInvoice(
            user: $this->user,
            usdAmount: 1.65,
            currencySymbol: 'BNB',
            networkKey: 'BNB'
        );

        // Simulate 0.00254816 BNB received on-chain (within 2% tolerance of expected ~0.00257813 BNB)
        $weiHex = '0x' . dechex((int) round(0.00254816 * 1e18));
        $blockCall = 0;

        \Illuminate\Support\Facades\Http::fake(function ($request) use ($weiHex, &$blockCall) {
            $body = $request->data();
            $method = $body['method'] ?? '';

            if ($method === 'eth_blockNumber') {
                $blockCall++;
                // First scan at block 1000, second scan at block 1020 (>= 15 confirmations)
                $blockHex = $blockCall === 1 ? '0x3e8' : '0x3fc';
                return \Illuminate\Support\Facades\Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => $blockHex], 200);
            }

            if ($method === 'eth_getBalance') {
                return \Illuminate\Support\Facades\Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => $weiHex], 200);
            }

            return \Illuminate\Support\Facades\Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => null], 200);
        });

        // First poll detects deposit and sets CONFIRMING
        $afterFirst = $scanner->scanAndVerify($invoice);
        $this->assertEquals(CryptoPaymentInvoice::STATUS_CONFIRMING, $afterFirst->status);

        // Second poll after 15+ blocks completes and credits wallet
        $afterSecond = $scanner->scanAndVerify($afterFirst);
        $this->assertEquals(CryptoPaymentInvoice::STATUS_COMPLETED, $afterSecond->status);
        $this->assertNotNull($afterSecond->credited_at);

        $wallet = UserWallet::where('user_id', $this->user->id)->first();
        $this->assertNotNull($wallet);
        $this->assertGreaterThan(0, $wallet->balance);
    }
}


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Wallet\DispatchAutomaticSweep;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\GasReserve;
use App\Models\Wallet\RecoveryQueueItem;
use App\Models\Wallet\SweepTransaction;
use App\Models\Wallet\TreasuryWallet;
use App\Models\Wallet\WalletAuditLog;
use App\Services\Security\SigningGateway;
use App\Services\Security\TransactionPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminHdTreasuryController extends Controller
{
    public function index(TransactionPolicy $policy, SigningGateway $signingGateway)
    {
        $signerHealth = $signingGateway->provider()->healthCheck();

        return view('admin.crypto.treasury', [
            'outboundPaused' => $policy->isOutboundPaused(),
            'testnetOnly' => (bool) config('custody.testnet_only', true),
            'signerHealth' => $signerHealth,
            'totalDeposits' => Deposit::count(),
            'pendingDeposits' => Deposit::whereNull('credited_at')->whereNotIn('status', [Deposit::STATUS_FAILED, Deposit::STATUS_REORGED])->count(),
            'confirmedDeposits' => Deposit::whereNotNull('credited_at')->count(),
            'failedItems' => RecoveryQueueItem::whereIn('status', ['PENDING', 'ESCALATED'])->count(),
            'networks' => BlockchainNetwork::with(['checkpoint', 'assets'])->orderBy('id')->get(),
            'treasuryWallets' => TreasuryWallet::with(['network', 'asset'])->orderBy('id')->get(),
            'gasReserves' => GasReserve::with(['network', 'asset'])->orderBy('id')->get(),
            'sweeps' => SweepTransaction::with(['network', 'treasuryWallet', 'deposit.asset'])->latest()->limit(20)->get(),
            'auditLogs' => WalletAuditLog::with('actor')->latest('id')->limit(20)->get(),
        ]);
    }

    public function toggleNetwork(Request $request, int $id): RedirectResponse
    {
        $network = BlockchainNetwork::findOrFail($id);
        $nextState = !$network->is_enabled;

        if ($nextState && !$network->is_testnet && (bool) config('custody.testnet_only', true)) {
            return back()->with('error', "Cannot enable mainnet [{$network->network_code}] while CUSTODY_TESTNET_ONLY=true.");
        }

        $network->update(['is_enabled' => $nextState]);

        WalletAuditLog::create([
            'actor_id' => Auth::id(),
            'action' => $nextState ? 'ADMIN_NETWORK_ENABLED' : 'ADMIN_NETWORK_DISABLED',
            'resource_type' => 'blockchain_network',
            'resource_id' => (string) $network->id,
            'metadata' => ['network_code' => $network->network_code],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('success', "{$network->network_code} is now " . ($nextState ? 'ENABLED' : 'DISABLED') . '.');
    }

    public function updateNetworkThreshold(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'confirmation_threshold' => ['required', 'integer', 'min:1', 'max:256'],
        ]);

        $network = BlockchainNetwork::findOrFail($id);
        $network->update(['confirmation_threshold' => (int) $validated['confirmation_threshold']]);

        WalletAuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'ADMIN_CONFIRMATION_THRESHOLD_UPDATED',
            'resource_type' => 'blockchain_network',
            'resource_id' => (string) $network->id,
            'metadata' => ['network_code' => $network->network_code, 'threshold' => (int) $validated['confirmation_threshold']],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('success', "Updated confirmation threshold for {$network->network_code}.");
    }

    public function toggleAsset(Request $request, int $id): RedirectResponse
    {
        $asset = CryptoAsset::with('network')->findOrFail($id);
        $asset->update(['is_enabled' => !$asset->is_enabled]);

        WalletAuditLog::create([
            'actor_id' => Auth::id(),
            'action' => $asset->is_enabled ? 'ADMIN_ASSET_ENABLED' : 'ADMIN_ASSET_DISABLED',
            'resource_type' => 'crypto_asset',
            'resource_id' => (string) $asset->id,
            'metadata' => ['symbol' => $asset->symbol, 'network_code' => $asset->network?->network_code],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('success', "{$asset->symbol} updated.");
    }

    public function retrySweep(Request $request, int $id): RedirectResponse
    {
        $sweep = SweepTransaction::findOrFail($id);
        DispatchAutomaticSweep::dispatch($sweep->deposit_id)
            ->onQueue((string) config('custody.queues.sweeping', 'sweeping'));

        WalletAuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'ADMIN_SWEEP_RETRY_QUEUED',
            'resource_type' => 'sweep_transaction',
            'resource_id' => (string) $sweep->id,
            'metadata' => ['deposit_id' => $sweep->deposit_id],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('success', "Sweep #{$sweep->id} queued for retry.");
    }

    public function pauseOutbound(Request $request, TransactionPolicy $policy): RedirectResponse
    {
        $policy->pauseOutbound();
        WalletAuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'ADMIN_EMERGENCY_OUTBOUND_PAUSE',
            'resource_type' => 'system_policy',
            'resource_id' => 'outbound_sweeps',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Outbound sweeps PAUSED.');
    }

    public function resumeOutbound(Request $request, TransactionPolicy $policy): RedirectResponse
    {
        $policy->resumeOutbound();
        WalletAuditLog::create([
            'actor_id' => Auth::id(),
            'action' => 'ADMIN_EMERGENCY_OUTBOUND_RESUME',
            'resource_type' => 'system_policy',
            'resource_id' => 'outbound_sweeps',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Outbound sweeps RESUMED.');
    }
}

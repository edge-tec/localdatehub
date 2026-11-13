<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\Wallet\DispatchAutomaticSweep;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\BlockchainTransaction;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\GasReserve;
use App\Models\Wallet\RecoveryQueueItem;
use App\Models\Wallet\SweepTransaction;
use App\Models\Wallet\TreasuryWallet;
use App\Models\Wallet\WalletAddress;
use App\Models\Wallet\WalletAuditLog;
use App\Services\Security\SigningGateway;
use App\Services\Security\TransactionPolicy;
use App\Services\Treasury\ReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTreasuryController extends Controller
{
    public function overview(TransactionPolicy $policy, ReconciliationService $reconciliation): JsonResponse
    {
        return response()->json([
            'outbound_paused' => $policy->isOutboundPaused(),
            'active_deposit_addresses' => WalletAddress::where('status', 'ACTIVE')->count(),
            'confirmed_deposits_count' => Deposit::whereNotNull('credited_at')->count(),
            'pending_sweeps_count' => SweepTransaction::whereIn('status', ['SWEEP_QUEUED', 'SWEEPING'])->count(),
            'failed_or_recovery_count' => RecoveryQueueItem::whereIn('status', ['PENDING', 'ESCALATED'])->count(),
            'reconciliation' => $reconciliation->reconcile(),
        ]);
    }

    public function wallets(): JsonResponse
    {
        return response()->json([
            'treasury_wallets' => TreasuryWallet::with(['network', 'asset'])->orderBy('id')->get(),
        ]);
    }

    public function transactions(): JsonResponse
    {
        return response()->json(
            BlockchainTransaction::with(['network', 'asset'])->latest()->paginate(30)
        );
    }

    public function sweeps(): JsonResponse
    {
        return response()->json(
            SweepTransaction::with(['network', 'treasuryWallet', 'deposit.asset'])->latest()->paginate(30)
        );
    }

    public function recovery(): JsonResponse
    {
        return response()->json(
            RecoveryQueueItem::latest()->paginate(30)
        );
    }

    public function gasReserves(): JsonResponse
    {
        return response()->json([
            'gas_reserves' => GasReserve::with(['network', 'asset', 'treasuryWallet'])->get(),
        ]);
    }

    public function networks(): JsonResponse
    {
        return response()->json([
            'networks' => BlockchainNetwork::with('checkpoint')->orderBy('id')->get(),
        ]);
    }

    public function health(SigningGateway $signingGateway): JsonResponse
    {
        return response()->json([
            'custody' => $signingGateway->provider()->healthCheck(),
            'networks' => BlockchainNetwork::with('checkpoint')->get()->map(fn ($n) => [
                'network_code' => $n->network_code,
                'is_enabled' => $n->is_enabled,
                'last_processed_height' => $n->checkpoint?->last_processed_height,
                'last_processed_at' => $n->checkpoint?->last_processed_at,
            ]),
        ]);
    }

    public function addresses(): JsonResponse
    {
        return response()->json(
            WalletAddress::with(['user:id,name,email', 'network', 'asset'])->latest()->paginate(30)
        );
    }

    public function auditLogs(): JsonResponse
    {
        return response()->json(
            WalletAuditLog::with('actor:id,name,email')->latest('id')->paginate(50)
        );
    }

    public function retrySweep(Request $request, int $id): JsonResponse
    {
        $sweep = SweepTransaction::findOrFail($id);
        DispatchAutomaticSweep::dispatch($sweep->deposit_id)
            ->onQueue((string) config('custody.queues.sweeping', 'sweeping'));

        WalletAuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => 'ADMIN_SWEEP_RETRY_QUEUED',
            'resource_type' => 'sweep_transaction',
            'resource_id' => (string) $sweep->id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['status' => 'queued', 'sweep_id' => $sweep->id]);
    }

    public function emergencyPause(Request $request, TransactionPolicy $policy): JsonResponse
    {
        $policy->pauseOutbound();

        WalletAuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => 'EMERGENCY_OUTBOUND_PAUSED',
            'resource_type' => 'treasury_policy',
            'resource_id' => 'global',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['outbound_paused' => true]);
    }

    public function emergencyResume(Request $request, TransactionPolicy $policy): JsonResponse
    {
        $policy->resumeOutbound();

        WalletAuditLog::create([
            'actor_id' => $request->user()?->id,
            'action' => 'EMERGENCY_OUTBOUND_RESUMED',
            'resource_type' => 'treasury_policy',
            'resource_id' => 'global',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['outbound_paused' => false]);
    }
}


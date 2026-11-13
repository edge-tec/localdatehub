<?php

namespace App\Livewire\Admin;

use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\GasReserve;
use App\Models\Wallet\GasSponsorshipTransaction;
use App\Models\Wallet\RecoveryQueueItem;
use App\Models\Wallet\SigningRequest;
use App\Models\Wallet\SweepTransaction;
use App\Models\Wallet\TreasuryWallet;
use App\Models\Wallet\WalletAddress;
use App\Models\Wallet\WalletAuditLog;
use App\Services\Security\SigningGateway;
use App\Services\Security\TransactionPolicy;
use App\Services\Treasury\ReconciliationService;
use Livewire\Attributes\On;
use Livewire\Component;

class TreasuryDashboard extends Component
{
    public string $activeTab = 'overview';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function toggleOutboundPause(TransactionPolicy $policy): void
    {
        if ($policy->isOutboundPaused()) {
            $policy->resumeOutbound();
            $action = 'EMERGENCY_OUTBOUND_RESUMED';
        } else {
            $policy->pauseOutbound();
            $action = 'EMERGENCY_OUTBOUND_PAUSED';
        }

        WalletAuditLog::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'resource_type' => 'treasury_policy',
            'resource_id' => 'global',
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    #[On('echo-private:admin.treasury,.deposit.updated')]
    #[On('echo-private:admin.treasury,.sweep.updated')]
    public function refreshRealtime(): void
    {
        // Triggers Livewire re-render on Reverb WebSocket event
    }

    public function render(
        TransactionPolicy $policy,
        ReconciliationService $reconciliation,
        SigningGateway $signingGateway
    ) {
        $recon = $reconciliation->reconcile();

        return view('livewire.admin.treasury-dashboard', [
            'outboundPaused' => $policy->isOutboundPaused(),
            'custodyHealth' => $signingGateway->provider()->healthCheck(),
            'reconciliation' => $recon,
            'activeAddressesCount' => WalletAddress::where('status', 'ACTIVE')->count(),
            'confirmedDepositsCount' => Deposit::whereNotNull('credited_at')->count(),
            'pendingSweepsCount' => SweepTransaction::whereIn('status', ['SWEEP_QUEUED', 'SWEEPING'])->count(),
            'failedOperationsCount' => RecoveryQueueItem::whereIn('status', ['PENDING', 'ESCALATED'])->count(),
            'networks' => BlockchainNetwork::with('checkpoint')->orderBy('id')->get(),
            'treasuryWallets' => TreasuryWallet::with(['network', 'asset'])->orderBy('id')->get(),
            'recentDeposits' => Deposit::with(['user:id,name,email', 'network', 'asset', 'blockchainTransaction'])->latest()->limit(15)->get(),
            'recentSweeps' => SweepTransaction::with(['network', 'treasuryWallet', 'deposit.asset'])->latest()->limit(15)->get(),
            'walletAddresses' => WalletAddress::with(['user:id,name,email', 'network', 'asset'])->latest()->limit(20)->get(),
            'signingRequests' => SigningRequest::with('network')->latest()->limit(20)->get(),
            'gasSponsorships' => GasSponsorshipTransaction::with('network')->latest()->limit(20)->get(),
            'recoveryItems' => RecoveryQueueItem::latest()->limit(15)->get(),
            'gasReserves' => GasReserve::with(['network', 'asset'])->get(),
            'auditLogs' => WalletAuditLog::with('actor:id,name')->latest('id')->limit(20)->get(),
        ]);
    }
}


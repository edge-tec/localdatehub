<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\LedgerEntry;
use App\Models\Wallet\WalletAddress;
use App\Services\Support\BaseUnitMath;
use Illuminate\Support\Facades\Auth;

class CryptoWalletDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $networks = BlockchainNetwork::where('is_enabled', true)
            ->with(['assets' => fn ($q) => $q->where('is_enabled', true)->orderBy('id')])
            ->orderBy('id')
            ->get();

        $assets = CryptoAsset::where('is_enabled', true)
            ->whereHas('network', fn ($q) => $q->where('is_enabled', true))
            ->with('network')
            ->orderBy('id')
            ->get();

        $addresses = WalletAddress::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->with(['network', 'asset'])
            ->get();

        $deposits = Deposit::where('user_id', $user->id)
            ->with(['network', 'asset', 'blockchainTransaction'])
            ->latest()
            ->paginate(15);

        $pendingDepositsCount = Deposit::where('user_id', $user->id)
            ->whereNull('credited_at')
            ->whereNotIn('status', [Deposit::STATUS_FAILED, Deposit::STATUS_REORGED])
            ->count();

        $entries = LedgerEntry::where('user_id', $user->id)
            ->with('asset.network')
            ->get();

        $balances = [];
        foreach ($entries as $entry) {
            $key = $entry->asset_id;
            if (!isset($balances[$key])) {
                $balances[$key] = [
                    'asset_id' => $entry->asset_id,
                    'symbol' => $entry->asset?->symbol ?? 'ASSET',
                    'decimals' => (int) ($entry->asset?->decimals ?? 18),
                    'network_code' => $entry->asset?->network?->network_code ?? '',
                    'network_name' => $entry->asset?->network?->name ?? '',
                    'balance_base_units' => '0',
                ];
            }
            $balances[$key]['balance_base_units'] = $entry->direction === 'CREDIT'
                ? BaseUnitMath::add($balances[$key]['balance_base_units'], (string) $entry->amount_base_units)
                : BaseUnitMath::sub($balances[$key]['balance_base_units'], (string) $entry->amount_base_units);
        }

        return view('account.crypto-wallet.index', [
            'user' => $user,
            'networks' => $networks,
            'assets' => $assets,
            'addresses' => $addresses,
            'deposits' => $deposits,
            'pendingDepositsCount' => $pendingDepositsCount,
            'balances' => array_values($balances),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\Deposit;
use App\Models\Wallet\LedgerEntry;
use App\Models\Wallet\WalletAddress;
use App\Services\Support\BaseUnitMath;
use App\Services\Wallet\HDWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        protected HDWalletService $hdWallet
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $addresses = WalletAddress::where('user_id', $user->id)->with(['network', 'asset'])->get();
        $recentDeposits = Deposit::where('user_id', $user->id)->with(['network', 'asset'])->latest()->limit(10)->get();

        return response()->json([
            'user_id' => $user->id,
            'active_addresses_count' => $addresses->count(),
            'recent_deposits' => $recentDeposits,
        ]);
    }

    public function networks(): JsonResponse
    {
        return response()->json([
            'networks' => BlockchainNetwork::where('is_enabled', true)->orderBy('id')->get(),
        ]);
    }

    public function assets(Request $request): JsonResponse
    {
        $query = CryptoAsset::where('is_enabled', true)->with('network');
        if ($request->filled('network_code')) {
            $code = strtoupper((string) $request->query('network_code'));
            $query->whereHas('network', fn ($q) => $q->where('network_code', $code));
        }

        return response()->json(['assets' => $query->get()]);
    }

    public function requestDepositAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'network_code' => ['required', 'string', 'max:32'],
            'asset_symbol' => ['required', 'string', 'max:24'],
        ]);

        $network = BlockchainNetwork::where('network_code', strtoupper($validated['network_code']))
            ->where('is_enabled', true)
            ->firstOrFail();

        $asset = CryptoAsset::where('network_id', $network->id)
            ->where('symbol', strtoupper($validated['asset_symbol']))
            ->where('is_enabled', true)
            ->firstOrFail();

        $address = $this->hdWallet->getOrAssignDepositAddress($request->user(), $network, $asset);

        return response()->json([
            'address' => $address->address,
            'network_code' => $network->network_code,
            'asset_symbol' => $asset->symbol,
            'derivation_path' => $address->derivation_path,
            'address_type' => $address->address_type,
        ], 201);
    }

    public function depositHistory(Request $request): JsonResponse
    {
        $deposits = Deposit::where('user_id', $request->user()->id)
            ->with(['network', 'asset', 'blockchainTransaction'])
            ->latest()
            ->paginate(25);

        return response()->json($deposits);
    }

    public function transactions(Request $request): JsonResponse
    {
        $entries = LedgerEntry::where('user_id', $request->user()->id)
            ->with('asset.network')
            ->latest('id')
            ->paginate(25);

        return response()->json($entries);
    }

    public function balances(Request $request): JsonResponse
    {
        $entries = LedgerEntry::where('user_id', $request->user()->id)
            ->with('asset.network')
            ->get();

        $balances = [];
        foreach ($entries as $entry) {
            $key = $entry->asset_id;
            if (!isset($balances[$key])) {
                $balances[$key] = [
                    'asset_id' => $entry->asset_id,
                    'symbol' => $entry->asset?->symbol,
                    'network_code' => $entry->asset?->network?->network_code,
                    'balance_base_units' => '0',
                ];
            }
            $balances[$key]['balance_base_units'] = $entry->direction === 'CREDIT'
                ? BaseUnitMath::add($balances[$key]['balance_base_units'], $entry->amount_base_units)
                : BaseUnitMath::sub($balances[$key]['balance_base_units'], $entry->amount_base_units);
        }

        return response()->json(['balances' => array_values($balances)]);
    }

    public function showDeposit(Request $request, int $id): JsonResponse
    {
        $deposit = Deposit::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with(['network', 'asset', 'blockchainTransaction', 'walletAddress'])
            ->firstOrFail();

        return response()->json(['deposit' => $deposit]);
    }
}


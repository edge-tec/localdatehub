<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Monetization\Models\CreditPackage;
use App\Modules\Monetization\Models\UserWallet;
use App\Modules\Monetization\Models\WalletTransaction;
use App\Modules\Monetization\Services\UserWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWalletController extends Controller
{
    public function __construct(
        protected UserWalletService $walletService
    ) {}

    /**
     * Credit Packages & System-Wide Wallet Ledger.
     */
    public function index(Request $request)
    {
        $packages = CreditPackage::orderBy('display_order')->get();

        $txQuery = WalletTransaction::with(['user', 'listing', 'actor'])->latest();

        if ($request->filled('type')) {
            $txQuery->where('type', $request->input('type'));
        }

        if ($request->filled('user_search')) {
            $q = $request->input('user_search');
            $txQuery->whereHas('user', function ($uq) use ($q) {
                $uq->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%")
                   ->orWhere('username', 'like', "%{$q}%");
            });
        }

        $transactions = $txQuery->paginate(25)->withQueryString();

        $stats = [
            'total_wallets' => UserWallet::count(),
            'total_circulating_credits' => UserWallet::sum('balance'),
            'total_purchased_credits' => UserWallet::sum('total_purchased'),
            'total_spent_credits' => UserWallet::sum('total_spent'),
        ];

        return view('admin.wallet.index', [
            'packages' => $packages,
            'transactions' => $transactions,
            'stats' => $stats,
        ]);
    }

    /**
     * Store Credit Package.
     */
    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'credits' => 'required|integer|min:1',
            'bonus_credits' => 'nullable|integer|min:0',
            'price_usd' => 'required|numeric|min:0.5',
            'display_order' => 'required|integer|min:1',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ]);

        CreditPackage::create([
            'name' => $validated['name'],
            'credits' => $validated['credits'],
            'bonus_credits' => $validated['bonus_credits'] ?: 0,
            'price_usd' => $validated['price_usd'],
            'display_order' => $validated['display_order'],
            'is_popular' => (bool) $request->input('is_popular', false),
            'is_active' => (bool) $request->input('is_active', true),
        ]);

        return back()->with('status', 'Credit package created successfully.');
    }

    /**
     * Update Credit Package.
     */
    public function updatePackage(Request $request, CreditPackage $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'credits' => 'required|integer|min:1',
            'bonus_credits' => 'nullable|integer|min:0',
            'price_usd' => 'required|numeric|min:0.5',
            'display_order' => 'required|integer|min:1',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $package->update([
            'name' => $validated['name'],
            'credits' => $validated['credits'],
            'bonus_credits' => $validated['bonus_credits'] ?: 0,
            'price_usd' => $validated['price_usd'],
            'display_order' => $validated['display_order'],
            'is_popular' => (bool) $request->input('is_popular', false),
            'is_active' => (bool) $request->input('is_active', false),
        ]);

        return back()->with('status', 'Credit package updated successfully.');
    }

    /**
     * Toggle active status of Credit Package.
     */
    public function togglePackage(CreditPackage $package)
    {
        $package->is_active = !$package->is_active;
        $package->save();

        $status = $package->is_active ? 'activated and is now live in checkout' : 'deactivated and hidden from checkout';
        return back()->with('status', "Credit package '{$package->name}' has been {$status}.");
    }

    /**
     * Delete Credit Package.
     */
    public function deletePackage(CreditPackage $package)
    {
        $package->delete();
        return back()->with('status', 'Credit package deleted.');
    }

    /**
     * Admin manual balance adjustment (Credit or Debit with audit trail).
     */
    public function manualAdjustment(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'action' => 'required|in:credit,debit',
            'amount' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $amount = (int) $validated['amount'];
        $admin = Auth::user();

        if ($validated['action'] === 'credit') {
            $this->walletService->credit(
                $user,
                $amount,
                WalletTransaction::TYPE_ADMIN_CREDIT,
                "Manual Admin Credit by {$admin->name}: {$validated['reason']}",
                $admin->id,
                null,
                "admin_credit_" . now()->timestamp . "_{$user->id}"
            );

            return back()->with('status', "Successfully credited {$amount} credits to {$user->name}.");
        } else {
            try {
                $this->walletService->debit(
                    $user,
                    $amount,
                    WalletTransaction::TYPE_ADMIN_DEBIT,
                    "Manual Admin Debit by {$admin->name}: {$validated['reason']}",
                    null,
                    $admin->id,
                    "admin_debit_" . now()->timestamp . "_{$user->id}"
                );

                return back()->with('status', "Successfully deducted {$amount} credits from {$user->name}.");
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
    }
}


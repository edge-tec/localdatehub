<?php

use App\Http\Controllers\Api\V1\AdminTreasuryController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth', 'active', 'throttle:60,1'])->prefix('wallet')->group(function () {
        Route::get('/overview', [WalletController::class, 'overview']);
        Route::get('/networks', [WalletController::class, 'networks']);
        Route::get('/assets', [WalletController::class, 'assets']);
        Route::post('/deposit/address', [WalletController::class, 'requestDepositAddress']);
        Route::get('/deposit/history', [WalletController::class, 'depositHistory']);
        Route::get('/transactions', [WalletController::class, 'transactions']);
        Route::get('/balances', [WalletController::class, 'balances']);
        Route::get('/deposit/{id}', [WalletController::class, 'showDeposit'])->whereNumber('id');
    });

    Route::middleware(['auth', 'admin.rbac', 'throttle:60,1'])->prefix('admin')->group(function () {
        Route::get('/treasury/overview', [AdminTreasuryController::class, 'overview']);
        Route::get('/treasury/wallets', [AdminTreasuryController::class, 'wallets']);
        Route::get('/treasury/transactions', [AdminTreasuryController::class, 'transactions']);
        Route::get('/treasury/sweeps', [AdminTreasuryController::class, 'sweeps']);
        Route::get('/treasury/recovery', [AdminTreasuryController::class, 'recovery']);
        Route::get('/treasury/gas-reserves', [AdminTreasuryController::class, 'gasReserves']);
        Route::get('/blockchain/networks', [AdminTreasuryController::class, 'networks']);
        Route::get('/blockchain/health', [AdminTreasuryController::class, 'health']);
        Route::get('/wallet/addresses', [AdminTreasuryController::class, 'addresses']);
        Route::get('/wallet/audit-logs', [AdminTreasuryController::class, 'auditLogs']);
        Route::post('/treasury/sweeps/{id}/retry', [AdminTreasuryController::class, 'retrySweep'])->whereNumber('id');
        Route::post('/treasury/emergency-pause', [AdminTreasuryController::class, 'emergencyPause']);
        Route::post('/treasury/emergency-resume', [AdminTreasuryController::class, 'emergencyResume']);
    });
});


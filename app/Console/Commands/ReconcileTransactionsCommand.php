<?php

namespace App\Console\Commands;

use App\Modules\Billing\Services\ReconciliationService;
use Illuminate\Console\Command;

class ReconcileTransactionsCommand extends Command
{
    protected $signature = 'monetization:reconcile';
    protected $description = 'Audit and identify unreconciled transactions';

    public function handle(ReconciliationService $reconciliationService): int
    {
        $unreconciled = $reconciliationService->getUnreconciledTransactions();

        if ($unreconciled->isEmpty()) {
            $this->info('All transactions are fully reconciled.');
            return self::SUCCESS;
        }

        $this->warn("Found {$unreconciled->count()} unreconciled transactions older than 2 hours:");
        foreach ($unreconciled as $txn) {
            $this->line("- #{$txn->id} Ref: {$txn->reference_id} Amount: \${$txn->amount_cents} Status: {$txn->status} Gateway: {$txn->gateway}");
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Modules\SEO\Jobs\ProcessIndexingQueueJob;
use Illuminate\Console\Command;

class SeoProcessIndexingCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'seo:indexing-process';

    /**
     * The console command description.
     */
    protected $description = 'Process pending URLs in the Google Indexing and discovery queue';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing Google Indexing queue...');
        dispatch_sync(new ProcessIndexingQueueJob());
        $this->info('Indexing queue processed successfully.');

        return self::SUCCESS;
    }
}


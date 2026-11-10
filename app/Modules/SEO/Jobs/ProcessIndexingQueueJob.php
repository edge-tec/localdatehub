<?php

namespace App\Modules\SEO\Jobs;

use App\Modules\SEO\Services\GoogleIndexingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIndexingQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(GoogleIndexingService $indexingService): void
    {
        $indexingService->processPendingQueue(30);
    }
}


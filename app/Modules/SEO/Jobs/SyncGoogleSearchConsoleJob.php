<?php

namespace App\Modules\SEO\Jobs;

use App\Modules\SEO\Services\GoogleSearchConsoleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGoogleSearchConsoleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(GoogleSearchConsoleService $gscService): void
    {
        $gscService->syncAnalytics();
    }
}


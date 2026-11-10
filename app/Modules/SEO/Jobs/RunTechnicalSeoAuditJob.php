<?php

namespace App\Modules\SEO\Jobs;

use App\Modules\SEO\Services\TechnicalSeoAuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunTechnicalSeoAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TechnicalSeoAuditService $auditService): void
    {
        $auditService->runFullAudit();
    }
}


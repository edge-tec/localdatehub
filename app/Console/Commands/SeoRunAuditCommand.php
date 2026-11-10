<?php

namespace App\Console\Commands;

use App\Modules\SEO\Services\TechnicalSeoAuditService;
use Illuminate\Console\Command;

class SeoRunAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'seo:audit-run';

    /**
     * The console command description.
     */
    protected $description = 'Execute automated technical SEO health audit and site scanner';

    /**
     * Execute the console command.
     */
    public function handle(TechnicalSeoAuditService $auditService): int
    {
        $this->info('Starting automated technical SEO audit...');
        $report = $auditService->runFullAudit();

        $this->table(['Audit Metric', 'Value'], [
            ['Total Scanned URLs', number_format($report['total_urls'] ?? 0)],
            ['Issues Found', number_format($report['issues_found'] ?? 0)],
            ['URLs With Issues', number_format($report['urls_with_issues'] ?? 0)],
            ['Overall Health Score', ($report['overall_health_score'] ?? 100) . ' / 100'],
            ['Audit Completed At', now()->toDateTimeString()],
        ]);

        return self::SUCCESS;
    }
}

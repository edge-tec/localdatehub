<?php

namespace App\Console\Commands;

use App\Modules\Verification\Services\IdentityVerificationService;
use Illuminate\Console\Command;

class AuditIdentityVerificationsCommand extends Command
{
    protected $signature = 'verification:audit {--dry-run : Report discrepancies without mutating user or listing flags}';

    protected $description = 'Audit all user accounts and listings to ensure Blue Verified Badges strictly match valid admin-approved ID verifications.';

    public function handle(IdentityVerificationService $service): int
    {
        $applyChanges = !$this->option('dry-run');
        $stats = $service->auditAllAccounts($applyChanges);

        $this->info($applyChanges ? 'Identity verification audit applied.' : 'Identity verification audit dry-run completed.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Users Scanned', $stats['users_scanned']],
                ['Legitimate ID-Verified Users Preserved', $stats['legitimate_verified_preserved']],
                ['Incorrect User Flags Cleared', $stats['incorrect_user_flags_cleared']],
                ['Incorrect Staff/Admin Flags Cleared', $stats['incorrect_admin_flags_cleared']],
                ['Incorrect Listing Poster Flags Cleared', $stats['incorrect_listing_flags_cleared']],
            ]
        );

        return self::SUCCESS;
    }
}


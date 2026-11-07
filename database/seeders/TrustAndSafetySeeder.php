<?php

namespace Database\Seeders;

use App\Modules\Moderation\Models\ModerationRule;
use App\Modules\Moderation\Models\PolicyVersion;
use App\Modules\Moderation\Models\StateCompliance;
use Illuminate\Database\Seeder;

class TrustAndSafetySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Prohibited-content moderation rules (keywords and regex patterns)
        $rules = [
            // Critical: Commercial sex work / Prostitution solicitation
            [
                'name' => 'Escort & Prostitution Solicitation',
                'rule_type' => 'regex',
                'pattern' => '/\b(escort\s+service|ppm|pay\s+per\s+meet|donation\s+required|hourly\s+rate|full\s+service|happy\s+ending|incall|outcall|roses\s+per\s+hour)\b/i',
                'action_severity' => 'auto_reject',
                'is_active' => true,
            ],
            // Critical: Trafficking & coercion indicators
            [
                'name' => 'Human Trafficking & Pimping Indicators',
                'rule_type' => 'regex',
                'pattern' => '/\b(new\s+girls\s+in\s+town|fresh\s+shipment|call\s+my\s+manager|pimp|book\s+through\s+driver|keep\s+your\s+passport|strictly\s+monitored)\b/i',
                'action_severity' => 'auto_suspend',
                'is_active' => true,
            ],
            // Critical: Minor exploitation
            [
                'name' => 'Underage & Minor References',
                'rule_type' => 'regex',
                'pattern' => '/\b(underage|teen\s+fresh|barely\s+18|schoolgirl|lolita|under\s+18|minor)\b/i',
                'action_severity' => 'auto_suspend',
                'is_active' => true,
            ],
            // High: Financial scam & advance fee fraud
            [
                'name' => 'Financial Scam & Wire Advance',
                'rule_type' => 'regex',
                'pattern' => '/\b(western\s+union|moneygram|cashapp\s+first|apple\s+gift\s+card|crypto\s+deposit|send\s+deposit\s+before\s+meet)\b/i',
                'action_severity' => 'flag',
                'is_active' => true,
            ],
            // High: Obfuscated phone / handle evasion
            [
                'name' => 'Obfuscated Phone Numbers',
                'rule_type' => 'regex',
                'pattern' => '/(\b\d{3}[-\s\.\*]{1,2}\d{3}[-\s\.\*]{1,2}\d{4}\b)|([0-9]{3}\s*(o|O|zero)\s*[0-9]{3})/i',
                'action_severity' => 'flag',
                'is_active' => true,
            ],
        ];

        foreach ($rules as $r) {
            ModerationRule::updateOrCreate(['name' => $r['name']], $r);
        }

        // 2. State-Specific Compliance Configurations
        // Certain US states have statutory age-verification and adult warning mandates
        $states = [
            [
                'state_code' => 'TX',
                'state_name' => 'Texas',
                'requires_age_gate' => true,
                'requires_id_verification' => true,
                'statutory_min_age' => 18,
                'mandatory_warning_text' => 'Texas Health & Safety Code Chapter 120 requires commercial adult entities to enforce strict digital age assurance. Verified 18+ attestation required.',
                'legal_reference' => 'TX HB 1181',
            ],
            [
                'state_code' => 'LA',
                'state_name' => 'Louisiana',
                'requires_age_gate' => true,
                'requires_id_verification' => true,
                'statutory_min_age' => 18,
                'mandatory_warning_text' => 'Louisiana Act 440 requires age verification before browsing adult personal classifieds or adult-oriented material.',
                'legal_reference' => 'LA Act 440 / La. R.S. 9:2800.28',
            ],
            [
                'state_code' => 'UT',
                'state_name' => 'Utah',
                'requires_age_gate' => true,
                'requires_id_verification' => true,
                'statutory_min_age' => 18,
                'mandatory_warning_text' => 'Utah Code 78B-6-2601 requires digital verification of age for adult content and personal connection services.',
                'legal_reference' => 'UT SB 287',
            ],
            [
                'state_code' => 'VA',
                'state_name' => 'Virginia',
                'requires_age_gate' => true,
                'requires_id_verification' => true,
                'statutory_min_age' => 18,
                'mandatory_warning_text' => 'Virginia Code § 18.2-391.2 requires age verification before accessing adult personal classifieds.',
                'legal_reference' => 'VA SB 1515',
            ],
            [
                'state_code' => 'CA',
                'state_name' => 'California',
                'requires_age_gate' => true,
                'requires_id_verification' => false,
                'statutory_min_age' => 18,
                'mandatory_warning_text' => 'California Online Privacy Protection Act & Cal. Civ. Code § 1798.100 (CCPA) disclosure. 18+ strictly enforced.',
                'legal_reference' => 'CalOPPA / CCPA',
            ],
            [
                'state_code' => 'FL',
                'state_name' => 'Florida',
                'requires_age_gate' => true,
                'requires_id_verification' => true,
                'statutory_min_age' => 18,
                'mandatory_warning_text' => 'Florida HB 3 statutory age assurance mandate for adult platforms.',
                'legal_reference' => 'FL HB 3',
            ],
        ];

        foreach ($states as $st) {
            StateCompliance::updateOrCreate(['state_code' => $st['state_code']], $st);
        }

        // 3. Official Policy Versions
        $policies = [
            [
                'policy_type' => PolicyVersion::TYPE_TERMS,
                'version' => '1.0',
                'title' => 'Terms of Service & Adult User Agreement',
                'summary_of_changes' => 'Initial comprehensive terms of service for LocalAdZone.',
                'content' => '<h2>1. Acceptance of Terms</h2><p>LocalAdZone is an adult personal classified advertising service restricted strictly to individuals who are at least 18 years of age (or the legal age of majority in their jurisdiction). Commercial sex solicitation, escort services, prostitution, human trafficking, and underage content are strictly prohibited and immediately reported to law enforcement.</p>',
                'is_active' => true,
                'effective_at' => now()->subMonths(1),
            ],
            [
                'policy_type' => PolicyVersion::TYPE_TRUST_SAFETY,
                'version' => '1.0',
                'title' => 'Trust, Safety & Zero-Tolerance Compliance Policy',
                'summary_of_changes' => 'Formal zero-tolerance anti-trafficking and anti-prostitution policy.',
                'content' => '<h2>Zero Tolerance Mandate</h2><p>In full compliance with federal laws including 18 U.S.C. §§ 2251, 2252, 2257, 2421A (FOSTA-SESTA) and 22 U.S.C. § 7101 (TVPA), LocalAdZone operates automated content scanning and trained human review to reject all unlawful solicitation.</p>',
                'is_active' => true,
                'effective_at' => now()->subMonths(1),
            ],
            [
                'policy_type' => PolicyVersion::TYPE_DMCA,
                'version' => '1.0',
                'title' => 'DMCA & Non-Consensual Imagery Takedown Policy',
                'summary_of_changes' => '17 U.S.C. 512 Notice and Takedown procedure.',
                'content' => '<h2>DMCA Notice &amp; Takedown</h2><p>Pursuant to Title 17, United States Code, Section 512(c)(2), copyright owners or designated agents may submit formal notices of claimed infringement through our automated DMCA portal.</p>',
                'is_active' => true,
                'effective_at' => now()->subMonths(1),
            ],
        ];

        foreach ($policies as $pol) {
            PolicyVersion::updateOrCreate(
                ['policy_type' => $pol['policy_type'], 'version' => $pol['version']],
                $pol
            );
        }
    }
}

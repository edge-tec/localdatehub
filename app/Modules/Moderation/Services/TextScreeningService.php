<?php

namespace App\Modules\Moderation\Services;

use App\Modules\Moderation\Models\ModerationRule;

class TextScreeningService
{
    public const RISK_LOW = 'low';
    public const RISK_MEDIUM = 'medium';
    public const RISK_HIGH = 'high';
    public const RISK_CRITICAL = 'critical';

    /**
     * Screen text content and assign an automated risk level, score, and flags.
     *
     * @param string $content Full title + description or message text
     * @return array{risk_level: string, score: int, flags: array<string>, action: string}
     */
    public function screen(string $content): array
    {
        $normalized = strtolower($content);
        $score = 0;
        $flags = [];
        $action = 'approve';

        // 1. High-severity zero-tolerance regex checks
        $criticalTrafficking = [
            'pimp', 'trafficking', 'new girls in town', 'fresh shipment',
            'call my manager', 'driver will deliver', 'keep your passport',
            'strictly monitored',
        ];
        foreach ($criticalTrafficking as $term) {
            if (str_contains($normalized, $term)) {
                $score += 90;
                $flags[] = "critical_trafficking_phrase: [{$term}]";
            }
        }

        $criticalUnderage = [
            'underage', 'schoolgirl', 'barely 18', 'under 18', 'teen fresh', 'minor',
        ];
        foreach ($criticalUnderage as $term) {
            if (str_contains($normalized, $term)) {
                $score += 95;
                $flags[] = "critical_underage_reference: [{$term}]";
            }
        }

        $criticalCommercialSex = [
            'escort service', 'pay per meet', 'ppm', 'hourly rate', 'donation required',
            'full service', 'happy ending', 'incall', 'outcall', 'roses per hour',
            'cash for meet', 'generous ppm',
        ];
        foreach ($criticalCommercialSex as $term) {
            if (str_contains($normalized, $term)) {
                $score += 85;
                $flags[] = "prohibited_solicitation_keyword: [{$term}]";
            }
        }

        // 2. High-risk financial scam indicators
        $financialScams = [
            'western union', 'moneygram', 'cashapp first', 'apple gift card',
            'send deposit before meet', 'crypto deposit', 'cashapp deposit',
        ];
        foreach ($financialScams as $term) {
            if (str_contains($normalized, $term)) {
                $score += 45;
                $flags[] = "financial_wire_scam_indicator: [{$term}]";
            }
        }

        // 3. Obfuscated phone patterns (e.g. 555-123-4567 or 555.123.4567)
        if (preg_match('/(\b\d{3}[-\s\.\*]{1,2}\d{3}[-\s\.\*]{1,2}\d{4}\b)/', $content)) {
            $score += 25;
            $flags[] = 'obfuscated_phone_pattern';
        }

        // 4. Evaluate against active database moderation rules
        try {
            $dbRules = ModerationRule::where('is_active', true)->get();
            foreach ($dbRules as $rule) {
                if ($rule->rule_type === 'regex') {
                    if (@preg_match($rule->pattern, $content)) {
                        $flags[] = "rule_triggered: {$rule->name}";
                        if ($rule->action_severity === 'auto_suspend') {
                            $score += 100;
                        } elseif ($rule->action_severity === 'auto_reject') {
                            $score += 80;
                        } else {
                            $score += 30;
                        }
                    }
                } elseif ($rule->rule_type === 'keyword') {
                    if (str_contains($normalized, strtolower($rule->pattern))) {
                        $flags[] = "keyword_triggered: {$rule->name}";
                        $score += 35;
                    }
                }
            }
        } catch (\Throwable) {
            // DB fallback if unseeded
        }

        // Calculate final risk level and recommended enforcement action
        if ($score >= 80) {
            $riskLevel = self::RISK_CRITICAL;
            $action = $score >= 95 ? 'auto_suspend' : 'auto_reject';
        } elseif ($score >= 50) {
            $riskLevel = self::RISK_HIGH;
            $action = 'quarantine';
        } elseif ($score >= 25) {
            $riskLevel = self::RISK_MEDIUM;
            $action = 'flag';
        } else {
            $riskLevel = self::RISK_LOW;
            $action = 'approve';
        }

        return [
            'risk_level' => $riskLevel,
            'score' => min(100, $score),
            'flags' => array_unique($flags),
            'action' => $action,
        ];
    }
}

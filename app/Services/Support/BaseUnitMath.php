<?php

namespace App\Services\Support;

class BaseUnitMath
{
    public static function add(string $a, string $b): string
    {
        if (function_exists('bcadd')) {
            return bcadd(self::normalize($a), self::normalize($b), 0);
        }

        return (string) ((int) $a + (int) $b);
    }

    public static function sub(string $a, string $b): string
    {
        if (function_exists('bcsub')) {
            return bcsub(self::normalize($a), self::normalize($b), 0);
        }

        return (string) ((int) $a - (int) $b);
    }

    public static function comp(string $a, string $b): int
    {
        $a = self::normalize($a);
        $b = self::normalize($b);

        if (function_exists('bccomp')) {
            return bccomp($a, $b, 0);
        }

        $aNeg = str_starts_with($a, '-');
        $bNeg = str_starts_with($b, '-');
        if ($aNeg !== $bNeg) {
            return $aNeg ? -1 : 1;
        }
        $aAbs = ltrim($a, '-');
        $bAbs = ltrim($b, '-');
        if (strlen($aAbs) !== strlen($bAbs)) {
            $cmp = strlen($aAbs) <=> strlen($bAbs);
            return $aNeg ? -$cmp : $cmp;
        }
        $cmp = strcmp($aAbs, $bAbs);
        return $aNeg ? -$cmp : ($cmp === 0 ? 0 : ($cmp > 0 ? 1 : -1));
    }

    public static function isPositive(string $a): bool
    {
        return self::comp($a, '0') > 0;
    }

    public static function normalize(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || !preg_match('/^-?\d+$/', $trimmed)) {
            throw new \InvalidArgumentException("Invalid integer base-unit amount: [{$value}].");
        }

        return $trimmed;
    }
}


<?php

namespace App\Support\Security;

use Illuminate\Support\Str;

class TwoFactorAuthService
{
    /**
     * Base32 character set (RFC 4648).
     */
    protected const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a cryptographically secure Base32 secret key (16 or 32 chars).
     */
    public function generateSecretKey(int $length = 16): string
    {
        $secret = '';
        $max = strlen(self::BASE32_CHARS) - 1;
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, $max)];
        }
        return $secret;
    }

    /**
     * Generate a set of emergency recovery codes.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::random(10) . '-' . Str::random(10);
        }
        return $codes;
    }

    /**
     * Generate an otpauth:// URI for authenticator apps (Google Authenticator, Authy, 1Password).
     */
    public function getOtpAuthUrl(string $company, string $holder, string $secret): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($company),
            rawurlencode($holder),
            $secret,
            rawurlencode($company)
        );
    }

    /**
     * Get the current 6-digit TOTP code for a secret key.
     */
    public function getCurrentCode(string $secret): string
    {
        $timeSlice = (int) floor(time() / 30);
        return $this->calculateCode($secret, $timeSlice);
    }

    /**
     * Alias for getCurrentCode.
     */
    public function generateCurrentOtp(string $secret): string
    {
        return $this->getCurrentCode($secret);
    }

    /**
     * Verify a 6-digit TOTP code against a secret key with a 1-interval time window leeway.
     */
    public function verifyKey(string $secret, string $code, int $discrepancy = 1): bool
    {
        $code = trim($code);
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }

        $currentTimeSlice = (int) floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->calculateCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate 6-digit TOTP code for a given time slice (RFC 6238).
     */
    protected function calculateCode(string $secret, int $timeSlice): string
    {
        $secretBinary = $this->base32Decode($secret);
        $timeData = pack('N*', 0) . pack('N*', $timeSlice);

        $hmac = hash_hmac('sha1', $timeData, $secretBinary, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;

        $unpacked = unpack('N', substr($hmac, $offset, 4));
        $value = ($unpacked[1] & 0x7FFFFFFF) % 1000000;

        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a Base32 encoded string into binary data.
     */
    protected function base32Decode(string $b32): string
    {
        $b32 = strtoupper($b32);
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($b32); $i++) {
            $char = $b32[$i];
            $val = strpos(self::BASE32_CHARS, $char);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}

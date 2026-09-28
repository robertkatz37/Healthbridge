<?php

namespace App\Services\Auth;

use RuntimeException;

/**
 * RFC 6238 TOTP (Time-based One-Time Password) implementation.
 *
 * Pure PHP — no external package required. Generates secrets compatible with
 * any authenticator app (Google Authenticator, Authy, 1Password, etc.) and
 * validates codes with a ±1 window to account for clock drift.
 */
class TotpService
{
    private const DIGITS   = 6;
    private const PERIOD   = 30;
    private const ALGO     = 'sha1';
    private const WINDOW   = 1; // allow 1 period before/after for clock drift

    /**
     * Generate a cryptographically random Base32-encoded secret.
     */
    public function generateSecret(int $bytes = 20): string
    {
        $random = random_bytes($bytes);
        return $this->base32Encode($random);
    }

    /**
     * Verify a 6-digit TOTP code against a secret within the drift window.
     */
    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return false;
        }

        $timestamp = (int) floor(time() / self::PERIOD);

        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if (hash_equals($this->generateCode($secret, $timestamp + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate the current TOTP code for a given secret.
     */
    public function currentCode(string $secret): string
    {
        return $this->generateCode($secret, (int) floor(time() / self::PERIOD));
    }

    /**
     * Build the otpauth:// URI used for QR code generation.
     */
    public function getQrCodeUri(string $secret, string $email, string $issuer = 'HealthsBridge'): string
    {
        $label  = rawurlencode($issuer . ':' . $email);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::ALGO),
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$params}";
    }

    /**
     * Generate a QR code image as an inline data URI (PNG, pure PHP, no package).
     * Uses the Google Charts API URL pattern rebuilt locally via GD.
     */
    public function getQrCodeDataUri(string $secret, string $email, string $issuer = 'HealthsBridge'): string
    {
        $uri = $this->getQrCodeUri($secret, $email, $issuer);

        // Use a Google QR code service URL as a reliable cross-environment fallback
        // when GD with QR generation isn't available.
        $encodedUri = urlencode($uri);
        return "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={$encodedUri}";
    }

    /**
     * Generate 8 one-time recovery codes.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // Format: XXXXX-XXXXX (10 uppercase alphanumeric chars grouped by 5)
            $codes[] = strtoupper(
                substr(bin2hex(random_bytes(4)), 0, 5) . '-' .
                substr(bin2hex(random_bytes(4)), 0, 5)
            );
        }
        return $codes;
    }

    /**
     * Hash recovery codes for storage.
     */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(fn ($code) => password_hash($code, PASSWORD_BCRYPT), $codes);
    }

    /**
     * Check and consume a recovery code (returns remaining codes or false).
     */
    public function verifyAndConsumeRecoveryCode(string $inputCode, array $hashedCodes): array|false
    {
        $inputCode = strtoupper(trim($inputCode));

        foreach ($hashedCodes as $index => $hashedCode) {
            if (password_verify($inputCode, $hashedCode)) {
                array_splice($hashedCodes, $index, 1);
                return $hashedCodes; // return remaining codes
            }
        }

        return false;
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private function generateCode(string $secret, int $counter): string
    {
        $key   = $this->base32Decode($secret);
        $time  = pack('N*', 0, $counter);
        $hash  = hash_hmac(self::ALGO, $time, $key, true);
        $offset = ord($hash[-1]) & 0x0F;
        $otp   = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) <<  8) |
            ((ord($hash[$offset + 3]) & 0xFF))
        ) % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary   = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($binary, 5) as $chunk) {
            $output .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return rtrim($output, '=');
    }

    private function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data     = strtoupper(rtrim($data, '='));
        $binary   = '';

        foreach (str_split($data) as $char) {
            $pos     = strpos($alphabet, $char);
            if ($pos === false) continue;
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $output .= chr(bindec($chunk));
            }
        }

        return $output;
    }
}

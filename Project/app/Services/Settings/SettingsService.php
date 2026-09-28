<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * The single point of read/write access to platform settings — nothing
 * else in the app should query the `settings` table directly. Two things
 * this centralizes deliberately:
 *
 *  1. Encryption: handled here in plain PHP, explicitly, rather than via
 *     an Eloquent mutator — see Setting model's docblock for the exact
 *     bug (silently storing a "sensitive" value as plaintext) that a
 *     mutator approach caused, caught via smoke testing before shipping.
 *  2. Caching: every setting is cached indefinitely and the cache is
 *     cleared on write, so reading settings (which happens on every
 *     request that renders branding, or on mail config boot) doesn't
 *     mean a database round-trip every time.
 */
class SettingsService
{
    private const CACHE_PREFIX = 'setting:';

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(self::CACHE_PREFIX . $key, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();

            return $setting ? $setting->castValue() : $default;
        });
    }

    /**
     * Raw (undecoded) string value — used when the caller wants the exact
     * stored string rather than a type-cast value (e.g. displaying it in
     * an edit form).
     */
    public function getRaw(string $key, ?string $default = null): ?string
    {
        $setting = Setting::where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public function set(string $key, mixed $value, string $group = 'general', string $type = 'string', bool $encrypted = false): Setting
    {
        $stringValue = match ($type) {
            // Explicitly normalizes the input rather than doing a bare
            // truthy check on $value — a bare `$value ? 'true' : 'false'`
            // would treat the STRING 'false' as truthy (PHP: any non-empty
            // string is truthy, including the string "false" itself),
            // silently inverting the setting. Caught via smoke testing:
            // seeding maintenance_mode with the string 'false' produced a
            // stored value of 'true'. filter_var's FILTER_VALIDATE_BOOLEAN
            // correctly parses string representations; real booleans pass
            // through filter_var unchanged too, so this is safe for both
            // calling conventions.
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
            'json' => json_encode($value),
            default => (string) $value,
        };

        // Encrypt in plain PHP BEFORE the value ever reaches Eloquent —
        // this is what actually fixes the mutator-ordering bug: there is
        // no attribute-order dependency possible when the ciphertext is
        // computed here and passed as a normal string.
        if ($encrypted && $stringValue !== '') {
            $stringValue = Crypt::encryptString($stringValue);
        }

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => $stringValue, 'type' => $type, 'is_encrypted' => $encrypted]
        );

        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting;
    }

    /**
     * All settings in a group, keyed by setting key, with castValue()
     * already applied — used to populate an entire settings form at once.
     */
    public function getGroup(string $group): array
    {
        return Setting::group($group)->get()->mapWithKeys(
            fn (Setting $setting) => [$setting->key => $setting->castValue()]
        )->toArray();
    }

    public function forget(string $key): void
    {
        Setting::where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX . $key);
    }
}

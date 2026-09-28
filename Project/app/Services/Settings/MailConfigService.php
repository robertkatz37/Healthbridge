<?php

namespace App\Services\Settings;

use Illuminate\Support\Facades\Config;

/**
 * Applies DB-stored mail settings to Laravel's runtime config, so an admin
 * can change SMTP credentials from the Settings UI and have them take
 * effect immediately — no .env edit, no server restart. Called from
 * AppServiceProvider::boot() on every request.
 *
 * Falls back silently to whatever .env/config/mail.php already has if no
 * DB settings exist yet (e.g. a fresh install before the admin has
 * visited the Mail Settings page) — this service only ever calls
 * Config::set() for keys it has an actual value for, it never clears an
 * existing config value.
 */
class MailConfigService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function apply(): void
    {
        $mailer = $this->settings->get('mail_mailer');
        if ($mailer) {
            Config::set('mail.default', $mailer);
        }

        if ($mailer === 'smtp') {
            $this->applySmtpConfig();
        }

        $fromAddress = $this->settings->get('branding_from_email');
        $fromName = $this->settings->get('branding_from_name');
        if ($fromAddress) {
            Config::set('mail.from.address', $fromAddress);
        }
        if ($fromName) {
            Config::set('mail.from.name', $fromName);
        }
    }

    private function applySmtpConfig(): void
    {
        $host = $this->settings->get('mail_host');
        $port = $this->settings->get('mail_port');
        $username = $this->settings->get('mail_username');
        $password = $this->settings->get('mail_password'); // transparently decrypted
        $encryption = $this->settings->get('mail_encryption');

        if ($host) {
            Config::set('mail.mailers.smtp.host', $host);
        }
        if ($port) {
            Config::set('mail.mailers.smtp.port', $port);
        }
        if ($username) {
            Config::set('mail.mailers.smtp.username', $username);
        }
        if ($password) {
            Config::set('mail.mailers.smtp.password', $password);
        }
        Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
    }
}

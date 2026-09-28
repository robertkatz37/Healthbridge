<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\SendTestEmailRequest;
use App\Http\Requests\Admin\Settings\UpdateMailSettingsRequest;
use App\Mail\TestEmail;
use App\Services\Settings\MailConfigService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MailSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly MailConfigService $mailConfig,
    ) {}

    public function edit(Request $request): View
    {
        $this->authorize('manage-platform-settings');

        $values = $this->settings->getGroup('mail');
        // Never send the decrypted password to the view — the form shows
        // a masked placeholder and only updates it if the admin types a
        // new value (see update() below).
        unset($values['mail_password']);
        $hasPasswordSet = $this->settings->getRaw('mail_password') !== '';

        return view('admin.settings.mail', compact('values', 'hasPasswordSet'));
    }

    public function update(UpdateMailSettingsRequest $request): RedirectResponse
    {
        $this->settings->set('mail_mailer', $request->mail_mailer, 'mail', 'string');
        $this->settings->set('mail_host', $request->mail_host ?? '', 'mail', 'string');
        $this->settings->set('mail_port', (string) $request->mail_port, 'mail', 'string');
        $this->settings->set('mail_username', $request->mail_username ?? '', 'mail', 'string');
        $this->settings->set('mail_encryption', $request->mail_encryption, 'mail', 'string');

        // Blank password field means "keep the current one" — never
        // overwrite a working encrypted credential with an empty string
        // just because the masked field was left untouched.
        if ($request->filled('mail_password')) {
            $this->settings->set('mail_password', $request->mail_password, 'mail', 'string', encrypted: true);
        }

        activity()->causedBy($request->user())->log('Mail settings updated');

        return back()->with('status', 'settings-updated');
    }

    public function sendTest(SendTestEmailRequest $request): RedirectResponse
    {
        // Re-apply DB settings immediately so a Save-then-Test in the same
        // session uses whatever was just saved, not last request's config.
        $this->mailConfig->apply();

        try {
            Mail::to($request->test_email)->send(new TestEmail($this->settings->get('platform_name', config('app.name'))));

            return back()->with('status', 'test-email-sent');
        } catch (\Exception $e) {
            return back()->withErrors(['test_email' => 'Failed to send test email: ' . $e->getMessage()]);
        }
    }
}

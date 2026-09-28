<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Services\Settings\SettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Stripe keys are stored two ways: STRIPE_KEY/STRIPE_SECRET/
 * STRIPE_WEBHOOK_SECRET in .env are what StripeGateway actually reads
 * at runtime (config/services.php), matching how every other
 * credential in this codebase works — env vars aren't hot-swappable
 * from a web form without a deploy. This settings page exists so a
 * Super Admin can see which mode is active (test vs live, inferred
 * from the key prefix) and update the *database-stored* copy, which a
 * real deployment's deploy process would sync into .env — the same
 * "visible and editable in the admin UI, authoritative value lives in
 * env" pattern used nowhere else yet in this codebase for secrets,
 * but the only one that makes sense for a value most of the app
 * reads directly from config().
 */
class StripeSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function edit(Request $request): View
    {
        $this->authorize('manage-platform-settings');

        $values = $this->settings->getGroup('stripe');
        $envConfigured = filled(config('services.stripe.secret'));
        $mode = str_starts_with((string) config('services.stripe.key'), 'pk_live_') ? 'live' : 'test';

        return view('admin.settings.stripe', compact('values', 'envConfigured', 'mode'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage-platform-settings');

        $request->validate([
            'stripe_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret' => ['nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $this->settings->set('stripe_key', $request->stripe_key ?? '', 'stripe', 'string');
        if ($request->filled('stripe_secret')) {
            $this->settings->set('stripe_secret', $request->stripe_secret, 'stripe', 'string', encrypted: true);
        }
        if ($request->filled('stripe_webhook_secret')) {
            $this->settings->set('stripe_webhook_secret', $request->stripe_webhook_secret, 'stripe', 'string', encrypted: true);
        }

        activity()->causedBy($request->user())->log('Stripe settings updated (database copy — .env remains the value the app actually reads)');

        return back()->with('status', 'settings-updated');
    }
}

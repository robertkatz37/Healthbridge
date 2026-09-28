<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TotpService $totp
    ) {}

    // ─── Challenge (login 2FA step) ───────────────────────────────────────────

    public function showChallenge(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('auth.2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor.challenge');
    }

    public function challenge(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $userId = $request->session()->get('auth.2fa.user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::findOrFail($userId);

        // Try TOTP code first, then recovery code
        if ($request->filled('code')) {
            if (!$this->totp->verify($user->two_factor_secret, $request->code)) {
                return back()->withErrors(['code' => 'The provided two-factor code is invalid.']);
            }
        } elseif ($request->filled('recovery_code')) {
            $hashedCodes = $user->getRecoveryCodesArray();
            $remaining   = $this->totp->verifyAndConsumeRecoveryCode($request->recovery_code, $hashedCodes);

            if ($remaining === false) {
                return back()->withErrors(['recovery_code' => 'The provided recovery code is invalid.']);
            }

            // Persist the consumed codes list
            $user->forceFill(['two_factor_recovery_codes' => json_encode($remaining)])->save();
        } else {
            return back()->withErrors(['code' => 'Please provide a two-factor code or recovery code.']);
        }

        $remember = $request->session()->pull('auth.2fa.remember', false);
        $request->session()->forget('auth.2fa.user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        activity()->causedBy($user)->log('2FA challenge passed');

        return redirect()->intended(route('dashboard'));
    }

    // ─── Setup (profile 2FA management) ──────────────────────────────────────

    public function setup(Request $request): View
    {
        $user = $request->user();

        // Generate a new secret if none exists yet
        if (!$user->two_factor_secret) {
            $secret = $this->totp->generateSecret();
            $user->forceFill(['two_factor_secret' => $secret])->save();
        }

        $qrCodeUrl = $this->totp->getQrCodeDataUri(
            $user->two_factor_secret,
            $user->email
        );

        return view('auth.two-factor.setup', [
            'qrCodeUrl'     => $qrCodeUrl,
            'secret'        => $user->two_factor_secret,
            'recoveryCodes' => $user->hasTwoFactorEnabled()
                ? $user->getRecoveryCodesArray()
                : [],
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return redirect()->route('two-factor.setup')
                ->withErrors(['code' => 'Please set up 2FA first.']);
        }

        if (!$this->totp->verify($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => 'The provided code is invalid. Please try again.']);
        }

        // Generate and hash recovery codes
        $plainCodes  = $this->totp->generateRecoveryCodes();
        $hashedCodes = $this->totp->hashRecoveryCodes($plainCodes);

        $user->forceFill([
            'two_factor_confirmed_at'  => now(),
            'two_factor_recovery_codes'=> json_encode($hashedCodes),
        ])->save();

        activity()->causedBy($user)->log('2FA enabled');

        // Pass plain codes to view once — they will not be shown again
        return redirect()->route('two-factor.recovery-codes')
            ->with('recovery_codes', $plainCodes)
            ->with('status', '2fa-enabled');
    }

    public function showRecoveryCodes(Request $request): View|RedirectResponse
    {
        if (!$request->user()->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.setup');
        }

        return view('auth.two-factor.recovery', [
            'recoveryCodes' => session('recovery_codes', []),
        ]);
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user        = $request->user();
        $plainCodes  = $this->totp->generateRecoveryCodes();
        $hashedCodes = $this->totp->hashRecoveryCodes($plainCodes);

        $user->forceFill([
            'two_factor_recovery_codes' => json_encode($hashedCodes),
        ])->save();

        activity()->causedBy($user)->log('2FA recovery codes regenerated');

        return redirect()->route('two-factor.recovery-codes')
            ->with('recovery_codes', $plainCodes)
            ->with('status', 'recovery-codes-regenerated');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        activity()->causedBy($user)->log('2FA disabled');

        return redirect()->route('profile.edit')
            ->with('status', '2fa-disabled');
    }
}

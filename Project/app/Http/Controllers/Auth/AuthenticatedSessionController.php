<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\LoginHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly LoginHistoryService $loginHistory
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        // Record successful login
        $this->loginHistory->recordSuccess($user, $request);

        // Update last_login metadata on the user record
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        // Log the event via Spatie ActivityLog (table exists from Phase 2)
        activity()
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip(), 'device' => $request->userAgent()])
            ->log('User logged in');

        // If 2FA is enabled, redirect to challenge before the dashboard
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('auth.2fa.user_id', $user->id);
            Auth::logout();
            $request->session()->put('auth.2fa.remember', $request->boolean('remember'));
            return redirect()->route('two-factor.challenge');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $this->loginHistory->recordLogout($user);
            activity()->causedBy($user)->log('User logged out');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

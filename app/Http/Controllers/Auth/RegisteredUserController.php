<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Handles role-aware registration for Family and Agency Owner accounts.
 * Admin, Advisor, Moderator, and other staff roles are NOT publicly
 * registerable per SRS §3.2 / FR-1 — they are created by Super Admin only.
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'              => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role'                  => ['required', 'in:family,agency_owner'],
            'phone'                 => ['nullable', 'string', 'max:20'],
            'terms'                 => ['required', 'accepted'],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign role based on selection
        $user->assignRole($request->role);

        // Create role-specific extension record
        if ($request->role === 'family') {
            Family::create([
                'user_id' => $user->id,
                'phone'   => $request->phone,
            ]);
        }
        // Agency Owner extension record created during the Agency Onboarding Wizard (Phase 7)

        event(new Registered($user));

        activity()
            ->causedBy($user)
            ->withProperties(['role' => $request->role, 'ip' => $request->ip()])
            ->log('User registered');

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}

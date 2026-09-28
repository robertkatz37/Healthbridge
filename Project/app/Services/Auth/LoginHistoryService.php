<?php

namespace App\Services\Auth;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Records every login attempt, success, and logout with device/IP context.
 * Feeds the Session Management view (profile → sessions) and the
 * suspicious-login detection in AuthenticatedSessionController.
 */
class LoginHistoryService
{
    /**
     * Record a successful login.
     */
    public function recordSuccess(User $user, Request $request): LoginHistory
    {
        return LoginHistory::create([
            'user_id'      => $user->id,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'device'       => $this->parseDevice($request->userAgent()),
            'status'       => 'success',
            'logged_in_at' => now(),
        ]);
    }

    /**
     * Record a failed login attempt.
     */
    public function recordFailure(string $email, Request $request): void
    {
        $user = User::where('email', $email)->first();
        if (!$user) return;

        LoginHistory::create([
            'user_id'      => $user->id,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'device'       => $this->parseDevice($request->userAgent()),
            'status'       => 'failed',
            'logged_in_at' => now(),
        ]);
    }

    /**
     * Mark the current session as logged out.
     */
    public function recordLogout(User $user): void
    {
        LoginHistory::where('user_id', $user->id)
            ->whereNull('logged_out_at')
            ->where('status', 'success')
            ->latest('logged_in_at')
            ->first()
            ?->update(['logged_out_at' => now()]);
    }

    /**
     * Return recent login history for display (last 10 entries).
     */
    public function recentForUser(User $user, int $limit = 10): \Illuminate\Support\Collection
    {
        return LoginHistory::where('user_id', $user->id)
            ->orderByDesc('logged_in_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Parse a human-readable device label from the user-agent string.
     * Simple approach: no external package required.
     */
    private function parseDevice(?string $ua): string
    {
        if (!$ua) return 'Unknown Device';

        $browser = 'Browser';
        if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg'))   $browser = 'Chrome';
        elseif (str_contains($ua, 'Firefox'))  $browser = 'Firefox';
        elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome'))  $browser = 'Safari';
        elseif (str_contains($ua, 'Edg'))      $browser = 'Edge';
        elseif (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident')) $browser = 'IE';

        $os = 'Unknown OS';
        if (str_contains($ua, 'Windows'))       $os = 'Windows';
        elseif (str_contains($ua, 'Macintosh')) $os = 'macOS';
        elseif (str_contains($ua, 'iPhone'))    $os = 'iPhone';
        elseif (str_contains($ua, 'iPad'))      $os = 'iPad';
        elseif (str_contains($ua, 'Android'))   $os = 'Android';
        elseif (str_contains($ua, 'Linux'))     $os = 'Linux';

        return "{$browser} on {$os}";
    }
}

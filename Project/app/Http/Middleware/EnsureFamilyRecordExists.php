<?php

namespace App\Http\Middleware;

use App\Models\Family;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarantees a Family row exists for the authenticated user before any
 * family.* controller runs. Fixes a real redirect-loop bug: a 'family'-role
 * user without a Family row (e.g. the role was assigned via the Admin
 * Panel rather than through registration — the only place that normally
 * creates one) would be sent to family.dashboard by ResolveWorkspace
 * (since 'family' is their one available workspace), which would then
 * redirect back to the generic /dashboard on finding no Family row —
 * landing right back on family.dashboard via ResolveWorkspace again.
 * Self-healing here, once, for the whole route group is more robust than
 * patching every individual family.* controller to handle the missing-row
 * case separately.
 */
class EnsureFamilyRecordExists
{
    public function handle(Request $request, Closure $next): Response
    {
        Family::firstOrCreate(['user_id' => $request->user()->id]);

        return $next($request);
    }
}

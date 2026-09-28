<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Reuses the general activity_log table (Spatie-style, already written to
 * throughout the app via the activity() helper) filtered to events this
 * family's user caused — no new table needed, consistent with how the
 * Admin Panel's agency status history/activity log pattern already works.
 */
class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('view', $family);

        $activities = DB::table('activity_log')
            ->where('causer_id', $request->user()->id)
            ->where('causer_type', \App\Models\User::class)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('family.activity', compact('activities'));
    }
}

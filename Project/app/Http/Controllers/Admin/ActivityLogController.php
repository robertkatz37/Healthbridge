<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLogEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Audit Log viewer — the platform has been writing to activity_log
 * (via the custom App\Helpers\ActivityLogger shim, schema-compatible
 * with Spatie Activitylog but not that package itself, which isn't
 * installed) since Phase 5, in nearly every controller across every
 * phase, but no admin UI ever existed to actually view it. Gated by
 * the more specific 'audit_logs.view' permission (already seeded,
 * Phase 6), not the broader platform-settings gate.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('audit_logs.view'), 403);

        $query = ActivityLogEntry::with(['causer', 'subject'])->latest();

        if ($request->filled('causer_type')) {
            $query->where('causer_type', $request->causer_type);
        }
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->paginate(30)->withQueryString();

        // Distinct subject types actually present, for the filter dropdown
        // — built from a lightweight query rather than a hardcoded list,
        // so it always reflects reality as new entity types start logging.
        $subjectTypes = ActivityLogEntry::select('subject_type')->distinct()->whereNotNull('subject_type')->pluck('subject_type');

        return view('admin.activity-log.index', compact('activities', 'subjectTypes'));
    }
}

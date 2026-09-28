<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Reads/manages Laravel's own `failed_jobs` table directly — this is
 * standard Laravel queue infrastructure, not a custom table, so retry/
 * delete reuse the framework's own artisan commands rather than
 * reimplementing job-retry semantics.
 */
class FailedEmailController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-platform-settings');

        $failedJobs = DB::table('failed_jobs')->latest('failed_at')->paginate(20);
        $pendingCount = DB::table('jobs')->count();

        return view('admin.settings.failed-emails.index', compact('failedJobs', 'pendingCount'));
    }

    public function retry(Request $request, string $uuid): RedirectResponse
    {
        $this->authorize('manage-platform-settings');

        Artisan::call('queue:retry', ['id' => [$uuid]]);

        activity()->causedBy($request->user())->log('Failed email job retried');

        return back()->with('status', 'job-retried');
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $this->authorize('manage-platform-settings');

        Artisan::call('queue:forget', ['id' => $uuid]);

        activity()->causedBy($request->user())->log('Failed email job deleted');

        return back()->with('status', 'job-deleted');
    }

    /**
     * Manually flushes the queue synchronously within this request —
     * appropriate for local/small-scale admin use so a Super Admin isn't
     * blocked on a persistent `queue:work` process just to test that
     * mail actually goes out. Production deployments should still run a
     * supervised queue worker (see DEPLOYMENT.md) — this button is a
     * convenience, not a replacement.
     */
    public function processQueue(Request $request): RedirectResponse
    {
        $this->authorize('manage-platform-settings');

        Artisan::call('queue:work', ['--stop-when-empty' => true, '--tries' => 3]);

        activity()->causedBy($request->user())->log('Email queue manually processed');

        return back()->with('status', 'queue-processed');
    }
}

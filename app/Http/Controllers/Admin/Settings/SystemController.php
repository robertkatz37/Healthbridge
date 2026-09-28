<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Read-only diagnostics page demonstrating the "System Settings
 * architecture for future platform settings" requirement — the
 * underlying Setting model/SettingsService already supports adding new
 * groups (e.g. a future 'security' or 'storage' group) with zero schema
 * changes; this page is the concrete first example beyond General/Mail/
 * Branding.
 */
class SystemController extends Controller
{
    public function show(Request $request): View
    {
        $this->authorize('manage-platform-settings');

        $info = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'database_connection' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_connection' => config('queue.default'),
            'session_driver' => config('session.driver'),
            'mail_driver' => config('mail.default'),
            'timezone' => config('app.timezone'),
        ];

        $counts = [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];

        return view('admin.settings.system', compact('info', 'counts'));
    }
}

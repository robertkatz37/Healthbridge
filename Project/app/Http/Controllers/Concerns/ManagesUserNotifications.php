<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Shared by Family\NotificationController (Phase 9) and
 * Advisor\NotificationController (Phase 11) — the logic (list, mark one
 * read, mark all read against $request->user()->notifications()) has no
 * role-specific dependency, so it was extracted here rather than
 * duplicated a third time. Each consuming class must declare its own
 * `protected string $notificationsView` property (not declared here —
 * PHP treats a trait property and a class property of the same name with
 * different default values as an incompatible composition error, so the
 * default lives only on the consuming class).
 */
trait ManagesUserNotifications
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(15);

        return view($this->notificationsView, compact('notifications'));
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'notifications-marked-read');
    }
}

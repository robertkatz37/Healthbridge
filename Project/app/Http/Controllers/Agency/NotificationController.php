<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Concerns\ManagesUserNotifications;
use App\Http\Controllers\Controller;

/**
 * Reuses the same shared trait as Family (Phase 9) and Advisor (Phase 11)
 * notification centers — the list/mark-read/mark-all-read logic has no
 * role-specific dependency. Agency owners already receive real
 * notifications today (AgencyApproved/AgencyRejected etc., Phase 8) with
 * no page to view them until now.
 */
class NotificationController extends Controller
{
    use ManagesUserNotifications;

    protected string $notificationsView = 'agency.notifications.index';
}

<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Concerns\ManagesUserNotifications;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    use ManagesUserNotifications;

    protected string $notificationsView = 'advisor.notifications.index';
}

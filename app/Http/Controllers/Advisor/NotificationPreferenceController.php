<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationPreferenceController extends Controller
{
    private const EVENTS = [
        'lead_assigned' => 'New lead assigned to you',
    ];

    public function edit(Request $request): View
    {
        $preferences = $request->user()->notification_preferences ?? [];

        return view('advisor.notification-preferences', ['events' => self::EVENTS, 'preferences' => $preferences]);
    }

    public function update(Request $request, NotificationPreferenceService $service): RedirectResponse
    {
        foreach (array_keys(self::EVENTS) as $event) {
            $service->set($request->user(), $event, 'mail', $request->boolean("{$event}_mail"));
            $service->set($request->user(), $event, 'database', $request->boolean("{$event}_database"));
        }

        return back()->with('status', 'preferences-updated');
    }
}

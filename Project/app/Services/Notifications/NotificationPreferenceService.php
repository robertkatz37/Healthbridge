<?php

namespace App\Services\Notifications;

use App\Models\User;

/**
 * Per-user, per-event, per-channel notification preferences, stored as a
 * single JSON column on `users` rather than a dedicated table — this is
 * simple key-value user settings, not data with its own lifecycle/audit
 * trail, so a new table would be overkill (contrast with the platform-
 * wide `settings` table from Phase 10, which genuinely needed grouping,
 * encryption, and caching).
 *
 * A missing key defaults to enabled, so existing users are unaffected
 * until they explicitly opt out of something.
 */
class NotificationPreferenceService
{
    public function isEnabled(User $user, string $event, string $channel): bool
    {
        return data_get($user->notification_preferences, "{$event}.{$channel}", true);
    }

    public function set(User $user, string $event, string $channel, bool $enabled): void
    {
        $prefs = $user->notification_preferences ?? [];
        $prefs[$event][$channel] = $enabled;
        $user->update(['notification_preferences' => $prefs]);
    }

    /**
     * Filters a candidate channel list (e.g. ['mail', 'database']) down to
     * only the channels this user has enabled for this event — used by
     * Notification::via() so opting out of email doesn't also silently
     * suppress the in-app notification.
     */
    public function enabledChannels(User $user, string $event, array $candidateChannels): array
    {
        return array_values(array_filter(
            $candidateChannels,
            fn (string $channel) => $this->isEnabled($user, $event, $channel)
        ));
    }
}

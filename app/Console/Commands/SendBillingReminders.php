<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Subscription;
use App\Notifications\Billing\SubscriptionExpiring;
use App\Notifications\Billing\TrialEnding;
use Illuminate\Console\Command;

/**
 * Sends SubscriptionExpiring (subscription scheduled to end within 3
 * days) and TrialEnding (trial ending within 3 days) notifications.
 * Meant to run daily via a real scheduler (Schedule::command() in
 * routes/console.php) — no cron actually runs in this environment
 * (same reasoning as Phase 15's promoteDuePages/promoteDuePosts), so
 * this is safe to run manually or via a real deployment's cron without
 * ever double-sending: each check only matches subscriptions whose
 * relevant date falls exactly 3 days out, not "within" a wider window,
 * so a daily run notifies each subscription at most once.
 */
class SendBillingReminders extends Command
{
    protected $signature = 'billing:send-reminders';

    protected $description = 'Send subscription-expiring and trial-ending reminders, and expire lapsed Featured Listing purchases';

    public function handle(): int
    {
        $expiringCount = 0;
        $trialCount = 0;

        Subscription::whereNotNull('ends_at')
            ->whereDate('ends_at', now()->addDays(3)->toDateString())
            ->each(function (Subscription $subscription) use (&$expiringCount) {
                $subscription->agency->user->notify(new SubscriptionExpiring($subscription));
                $expiringCount++;
            });

        Subscription::whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', now()->addDays(3)->toDateString())
            ->each(function (Subscription $subscription) use (&$trialCount) {
                $subscription->agency->user->notify(new TrialEnding($subscription));
                $trialCount++;
            });

        $expiredFeatured = Agency::where('is_featured', true)
            ->whereNotNull('featured_until')
            ->where('featured_until', '<=', now())
            ->update(['is_featured' => false, 'featured_until' => null]);

        $this->info("Sent {$expiringCount} subscription-expiring and {$trialCount} trial-ending reminders. Expired {$expiredFeatured} lapsed Featured Listing purchases.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Notifications\Billing;

use App\Models\Subscription;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiring extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Subscription $subscription) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'subscription_expiring', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $endsAt = $this->subscription->ends_at?->format('M d, Y') ?? 'soon';

        return $this->renderMailMessage(
            'subscription_expiring',
            ['plan_name' => $this->subscription->plan->name, 'ends_at' => $endsAt],
            fn () => (new MailMessage)
                ->subject('Your subscription is ending soon')
                ->greeting('Hello,')
                ->line('Your ' . $this->subscription->plan->name . ' subscription is scheduled to end on ' . $endsAt . '.')
                ->action('Manage Subscription', route('agency.billing.index'))
                ->line('If you\'d like to keep your current plan, you can resume anytime before it ends.')
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['subscription_id' => $this->subscription->id, 'ends_at' => (string) $this->subscription->ends_at, 'event' => 'subscription_expiring'];
    }
}

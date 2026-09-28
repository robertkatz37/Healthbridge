<?php

namespace App\Notifications\Billing;

use App\Models\Subscription;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialEnding extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Subscription $subscription) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'trial_ending', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trialEndsAt = $this->subscription->trial_ends_at?->format('M d, Y') ?? 'soon';

        return $this->renderMailMessage(
            'trial_ending',
            ['plan_name' => $this->subscription->plan->name, 'trial_ends_at' => $trialEndsAt],
            fn () => (new MailMessage)
                ->subject('Your free trial ends soon')
                ->greeting('Hello,')
                ->line('Your trial of the ' . $this->subscription->plan->name . ' plan ends on ' . $trialEndsAt . '.')
                ->line('Your card on file will be charged automatically once the trial ends, unless you cancel first.')
                ->action('Manage Subscription', route('agency.billing.index'))
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['subscription_id' => $this->subscription->id, 'trial_ends_at' => (string) $this->subscription->trial_ends_at, 'event' => 'trial_ending'];
    }
}

<?php

namespace App\Notifications\Referral;

use App\Models\Referral;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the Agency when an advisor sends them a Referral. Same queued
 * + RendersFromEmailTemplate + NotificationPreferenceService pattern as
 * every notification since Phase 8.
 */
class ReferralSent extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Referral $referral) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'referral_sent', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'referral_sent',
            ['family_name' => $this->referral->family_name, 'referral_url' => route('agency.referrals.show', $this->referral)],
            fn () => (new MailMessage)
                ->subject('New referral received — ' . $this->referral->family_name)
                ->greeting('Hello,')
                ->line('You have received a new referral for ' . $this->referral->family_name . '.')
                ->action('View Referral', route('agency.referrals.show', $this->referral))
                ->line('Please respond as soon as possible.')
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['referral_id' => $this->referral->id, 'family_name' => $this->referral->family_name, 'event' => 'referral_sent'];
    }
}

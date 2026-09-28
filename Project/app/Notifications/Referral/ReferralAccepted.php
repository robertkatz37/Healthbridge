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
 * Sent to both the Family and the Advisor when an Agency accepts a
 * Referral.
 */
class ReferralAccepted extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Referral $referral) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'referral_accepted', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $notifiable->hasRole('advisor') || $notifiable->hasRole('advisor_manager')
            ? route('advisor.leads.show', $this->referral->lead)
            : route('family.referrals.show', $this->referral);

        return $this->renderMailMessage(
            'referral_accepted',
            ['agency_name' => $this->referral->agency->name, 'referral_url' => $url],
            fn () => (new MailMessage)
                ->subject($this->referral->agency->name . ' accepted the referral')
                ->greeting('Good news,')
                ->line($this->referral->agency->name . ' has accepted the referral for ' . $this->referral->family_name . '.')
                ->action('View Details', $url)
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['referral_id' => $this->referral->id, 'agency_name' => $this->referral->agency->name, 'event' => 'referral_accepted'];
    }
}

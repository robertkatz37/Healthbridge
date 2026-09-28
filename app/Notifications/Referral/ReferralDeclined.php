<?php

namespace App\Notifications\Referral;

use App\Models\Referral;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralDeclined extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Referral $referral) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'referral_declined', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $notifiable->hasRole('advisor') || $notifiable->hasRole('advisor_manager')
            ? route('advisor.leads.show', $this->referral->lead)
            : route('family.referrals.show', $this->referral);

        return $this->renderMailMessage(
            'referral_declined',
            ['agency_name' => $this->referral->agency->name, 'reason' => $this->referral->closed_reason, 'referral_url' => $url],
            fn () => (new MailMessage)
                ->subject($this->referral->agency->name . ' declined the referral')
                ->greeting('Hello,')
                ->line($this->referral->agency->name . ' was unable to accept this referral.')
                ->when($this->referral->closed_reason, fn ($m) => $m->line('Reason: ' . $this->referral->closed_reason))
                ->action('View Details', $url)
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['referral_id' => $this->referral->id, 'agency_name' => $this->referral->agency->name, 'event' => 'referral_declined'];
    }
}

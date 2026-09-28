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
 * Covers Converted, Closed Lost, and Cancelled — all three are "this
 * referral has reached its end" from the notifiable's point of view,
 * differentiated only by the wording in the message body.
 */
class ReferralClosed extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Referral $referral) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'referral_closed', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->referral->status->label();

        return $this->renderMailMessage(
            'referral_closed',
            ['family_name' => $this->referral->family_name, 'agency_name' => $this->referral->agency->name, 'status' => $statusLabel],
            fn () => (new MailMessage)
                ->subject('Referral update — ' . $statusLabel)
                ->greeting('Hello,')
                ->line('The referral for ' . $this->referral->family_name . ' at ' . $this->referral->agency->name . ' is now: ' . $statusLabel . '.')
                ->when($this->referral->closed_reason, fn ($m) => $m->line('Reason: ' . $this->referral->closed_reason))
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['referral_id' => $this->referral->id, 'family_name' => $this->referral->family_name, 'status' => $this->referral->status->value, 'event' => 'referral_closed'];
    }
}

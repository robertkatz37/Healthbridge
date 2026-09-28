<?php

namespace App\Notifications\Referral;

use App\Models\Referral;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MoveInConfirmed extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Referral $referral) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'move_in_confirmed', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'move_in_confirmed',
            ['family_name' => $this->referral->family_name, 'agency_name' => $this->referral->agency->name],
            fn () => (new MailMessage)
                ->subject('Move-in confirmed — ' . $this->referral->family_name)
                ->greeting('Congratulations,')
                ->line('Move-in has been confirmed for ' . $this->referral->family_name . ' at ' . $this->referral->agency->name . '.')
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['referral_id' => $this->referral->id, 'family_name' => $this->referral->family_name, 'event' => 'move_in_confirmed'];
    }
}

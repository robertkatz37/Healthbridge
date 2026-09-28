<?php

namespace App\Notifications\Agency;

use App\Models\Agency;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgencyReactivated extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Agency $agency) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'agency_reactivated',
            ['agency_name' => $this->agency->name, 'dashboard_url' => route('agency.dashboard')],
            fn () => (new MailMessage)
                ->subject('Your agency listing is active again — ' . $this->agency->name)
                ->greeting('Good news!')
                ->line('Your agency listing "' . $this->agency->name . '" has been reactivated and is now visible to families again.')
                ->action('View Your Dashboard', route('agency.dashboard'))
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->name,
            'event' => 'agency_reactivated',
        ];
    }
}

<?php

namespace App\Notifications\Agency;

use App\Models\Agency;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgencySuspended extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(
        private readonly Agency $agency,
        private readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'agency_suspended',
            ['agency_name' => $this->agency->name, 'reason' => $this->reason],
            fn () => (new MailMessage)
                ->subject('Your agency listing has been suspended — ' . $this->agency->name)
                ->greeting('Hello,')
                ->line('Your agency listing "' . $this->agency->name . '" has been suspended and is no longer visible to families.')
                ->line('Reason: ' . $this->reason)
                ->line('Please contact our support team to resolve this and restore your listing.')
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->name,
            'reason' => $this->reason,
            'event' => 'agency_suspended',
        ];
    }
}

<?php

namespace App\Notifications\Agency;

use App\Models\Agency;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationRejected extends Notification implements ShouldQueue
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
            'agency_rejected',
            ['agency_name' => $this->agency->name, 'reason' => $this->reason],
            fn () => (new MailMessage)
                ->subject('Update on your agency application — ' . $this->agency->name)
                ->greeting('Hello,')
                ->line('After review, your application for "' . $this->agency->name . '" was not approved at this time.')
                ->line('Reason: ' . $this->reason)
                ->line('If you believe this was in error or would like to discuss further, please contact our support team.')
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->name,
            'reason' => $this->reason,
            'event' => 'agency_rejected',
        ];
    }
}

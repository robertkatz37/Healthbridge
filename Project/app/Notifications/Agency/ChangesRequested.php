<?php

namespace App\Notifications\Agency;

use App\Models\Agency;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangesRequested extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(
        private readonly Agency $agency,
        private readonly string $notes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'agency_changes_requested',
            ['agency_name' => $this->agency->name, 'notes' => $this->notes, 'dashboard_url' => route('agency.dashboard')],
            fn () => (new MailMessage)
                ->subject('Action needed on your agency application — ' . $this->agency->name)
                ->greeting('Hello,')
                ->line('Our team reviewed your application for "' . $this->agency->name . '" and needs a few changes before it can be approved.')
                ->line('Requested changes: ' . $this->notes)
                ->action('Update Your Listing', route('agency.dashboard'))
                ->line('Once you\'ve made the changes, you can resubmit for review from your dashboard.')
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->name,
            'notes' => $this->notes,
            'event' => 'agency_changes_requested',
        ];
    }
}

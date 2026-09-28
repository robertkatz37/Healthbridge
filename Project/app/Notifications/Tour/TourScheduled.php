<?php

namespace App\Notifications\Tour;

use App\Models\TourRequest;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TourScheduled extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly TourRequest $tour) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'tour_scheduled', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'tour_scheduled',
            ['agency_name' => $this->tour->agency->name, 'date' => $this->tour->requested_date->format('M d, Y')],
            fn () => (new MailMessage)
                ->subject('Tour scheduled — ' . $this->tour->agency->name)
                ->greeting('Hello,')
                ->line('A tour has been scheduled at ' . $this->tour->agency->name . ' on ' . $this->tour->requested_date->format('M d, Y') . '.')
                ->when($this->tour->requested_time_window, fn ($m) => $m->line('Time: ' . $this->tour->requested_time_window))
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['tour_id' => $this->tour->id, 'agency_name' => $this->tour->agency->name, 'event' => 'tour_scheduled'];
    }
}

<?php

namespace App\Notifications\Tour;

use App\Models\TourRequest;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TourCancelled extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly TourRequest $tour) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'tour_cancelled', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'tour_cancelled',
            ['agency_name' => $this->tour->agency->name],
            fn () => (new MailMessage)
                ->subject('Tour cancelled — ' . $this->tour->agency->name)
                ->greeting('Hello,')
                ->line('The tour at ' . $this->tour->agency->name . ' scheduled for ' . $this->tour->requested_date->format('M d, Y') . ' has been cancelled.')
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['tour_id' => $this->tour->id, 'agency_name' => $this->tour->agency->name, 'event' => 'tour_cancelled'];
    }
}

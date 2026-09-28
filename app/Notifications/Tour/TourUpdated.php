<?php

namespace App\Notifications\Tour;

use App\Models\TourRequest;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Covers reschedule and status changes (confirmed/completed) — any
 * update to an existing tour that isn't a cancellation.
 */
class TourUpdated extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly TourRequest $tour, private readonly string $changeSummary) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'tour_updated', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'tour_updated',
            ['agency_name' => $this->tour->agency->name, 'change_summary' => $this->changeSummary],
            fn () => (new MailMessage)
                ->subject('Tour updated — ' . $this->tour->agency->name)
                ->greeting('Hello,')
                ->line('Your tour at ' . $this->tour->agency->name . ' has been updated: ' . $this->changeSummary)
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['tour_id' => $this->tour->id, 'agency_name' => $this->tour->agency->name, 'change_summary' => $this->changeSummary, 'event' => 'tour_updated'];
    }
}

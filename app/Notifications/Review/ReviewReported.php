<?php

namespace App\Notifications\Review;

use App\Models\ReviewReport;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewReported extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly ReviewReport $report) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'review_reported', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $agencyName = $this->report->review->agency->name;

        return $this->renderMailMessage(
            'review_reported',
            ['agency_name' => $agencyName, 'reason' => $this->report->reason->label(), 'moderation_url' => route('admin.reviews.index', ['filter' => 'reported'])],
            fn () => (new MailMessage)
                ->subject('A review was reported — ' . $agencyName)
                ->greeting('Hello,')
                ->line('A review for ' . $agencyName . ' was reported for: ' . $this->report->reason->label())
                ->action('View Reported Reviews', route('admin.reviews.index', ['filter' => 'reported']))
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['review_id' => $this->report->review_id, 'reason' => $this->report->reason->value, 'event' => 'review_reported'];
    }
}

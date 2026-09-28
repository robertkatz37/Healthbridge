<?php

namespace App\Notifications\Review;

use App\Models\Review;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewRejected extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Review $review, private readonly string $reason) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'review_rejected', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'review_rejected',
            ['agency_name' => $this->review->agency->name, 'reason' => $this->reason],
            fn () => (new MailMessage)
                ->subject('Update on your submitted review')
                ->greeting('Hello,')
                ->line('Your review for ' . $this->review->agency->name . ' was not approved for publication.')
                ->line('Reason: ' . $this->reason)
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['review_id' => $this->review->id, 'agency_name' => $this->review->agency->name, 'reason' => $this->reason, 'event' => 'review_rejected'];
    }
}

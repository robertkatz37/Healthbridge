<?php

namespace App\Notifications\Review;

use App\Models\Review;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewSubmitted extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Review $review) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'review_submitted', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'review_submitted',
            ['agency_name' => $this->review->agency->name, 'moderation_url' => route('admin.reviews.index')],
            fn () => (new MailMessage)
                ->subject('New review awaiting moderation')
                ->greeting('Hello,')
                ->line('A new review for ' . $this->review->agency->name . ' is awaiting moderation.')
                ->action('Review Moderation Queue', route('admin.reviews.index'))
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['review_id' => $this->review->id, 'agency_name' => $this->review->agency->name, 'event' => 'review_submitted'];
    }
}

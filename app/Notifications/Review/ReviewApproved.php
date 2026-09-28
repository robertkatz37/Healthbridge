<?php

namespace App\Notifications\Review;

use App\Models\Review;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewApproved extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Review $review) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'review_approved', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'review_approved',
            ['agency_name' => $this->review->agency->name, 'review_url' => route('agencies.show', $this->review->agency)],
            fn () => (new MailMessage)
                ->subject('Your review is now live')
                ->greeting('Thank you,')
                ->line('Your review for ' . $this->review->agency->name . ' has been approved and is now visible to other families.')
                ->action('View Agency Profile', route('agencies.show', $this->review->agency))
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['review_id' => $this->review->id, 'agency_name' => $this->review->agency->name, 'event' => 'review_approved'];
    }
}

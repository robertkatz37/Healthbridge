<?php

namespace App\Notifications\Review;

use App\Models\Review;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgencyResponseAdded extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Review $review) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'agency_response_added', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'agency_response_added',
            ['agency_name' => $this->review->agency->name, 'review_url' => route('agencies.show', $this->review->agency)],
            fn () => (new MailMessage)
                ->subject($this->review->agency->name . ' responded to your review')
                ->greeting('Hello,')
                ->line($this->review->agency->name . ' has posted a response to your review.')
                ->action('View Response', route('agencies.show', $this->review->agency))
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['review_id' => $this->review->id, 'agency_name' => $this->review->agency->name, 'event' => 'agency_response_added'];
    }
}

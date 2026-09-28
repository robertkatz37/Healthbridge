<?php

namespace App\Notifications\Agency;

use App\Models\Agency;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued (Phase 10 — "Email Queue support"): dispatched to the database
 * queue (QUEUE_CONNECTION=database) rather than sent synchronously. A
 * queue worker (`php artisan queue:work`) must be running for this to
 * actually send — see the Admin Settings > Email Queue screen, which
 * shows pending/failed counts and lets an admin manually flush the queue
 * for local/small-scale use without a persistent worker.
 */
class ApplicationApproved extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Agency $agency) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'agency_approved',
            ['agency_name' => $this->agency->name, 'dashboard_url' => route('agency.dashboard')],
            fn () => (new MailMessage)
                ->subject('Your agency listing is now live — ' . $this->agency->name)
                ->greeting('Congratulations!')
                ->line('Your agency "' . $this->agency->name . '" has been approved and is now published on HealthsBridge.')
                ->line('Families can now discover your listing and reach out about care.')
                ->action('View Your Dashboard', route('agency.dashboard'))
                ->line('Thank you for partnering with HealthsBridge.')
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->name,
            'event' => 'agency_approved',
        ];
    }
}

<?php

namespace App\Notifications\Advisor;

use App\Models\Lead;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued (consistent with the Agency moderation notifications, Phase 8/10)
 * and checks for an active EmailTemplate before falling back to hardcoded
 * content, via the same RendersFromEmailTemplate trait — satisfies both
 * "Email Notifications" and "In-App Notifications" (mail + database
 * channels) from the Phase 11 requirements. Respects per-user
 * notification preferences (Phase 11 completion pass) so an advisor who
 * has opted out of email for this event still gets the in-app one.
 */
class LeadAssigned extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Lead $lead) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)
            ->enabledChannels($notifiable, 'lead_assigned', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'lead_assigned',
            [
                'family_name' => $this->lead->family_name,
                'lead_url' => route('advisor.leads.show', $this->lead),
            ],
            fn () => (new MailMessage)
                ->subject('New lead assigned — ' . $this->lead->family_name)
                ->greeting('Hello,')
                ->line('A new lead has been assigned to you: ' . $this->lead->family_name . '.')
                ->action('View Lead', route('advisor.leads.show', $this->lead))
                ->line('Please reach out to the family as soon as possible.')
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lead_id' => $this->lead->id,
            'family_name' => $this->lead->family_name,
            'event' => 'lead_assigned',
        ];
    }
}

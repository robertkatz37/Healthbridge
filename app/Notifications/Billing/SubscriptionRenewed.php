<?php

namespace App\Notifications\Billing;

use App\Models\Invoice;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewed extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'subscription_renewed', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'subscription_renewed',
            ['invoice_number' => $this->invoice->invoice_number, 'amount' => number_format((float) $this->invoice->total_amount, 2)],
            fn () => (new MailMessage)
                ->subject('Your subscription has renewed')
                ->greeting('Hello,')
                ->line('Your HealthsBridge subscription has renewed successfully — $' . number_format((float) $this->invoice->total_amount, 2) . ' was charged.')
                ->action('View Billing', route('agency.billing.index'))
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['invoice_id' => $this->invoice->id, 'event' => 'subscription_renewed'];
    }
}

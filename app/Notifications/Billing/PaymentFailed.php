<?php

namespace App\Notifications\Billing;

use App\Models\Invoice;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(
        private readonly Invoice $invoice,
        private readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'payment_failed', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'payment_failed',
            ['invoice_number' => $this->invoice->invoice_number, 'reason' => $this->reason],
            fn () => (new MailMessage)
                ->subject('Payment failed — Invoice ' . $this->invoice->invoice_number)
                ->greeting('Hello,')
                ->line('We were unable to process your payment for invoice ' . $this->invoice->invoice_number . '.')
                ->line('Reason: ' . $this->reason)
                ->action('Update Payment Method', route('agency.billing.index'))
                ->line('Please update your payment details to avoid interruption to your service.')
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['invoice_id' => $this->invoice->id, 'reason' => $this->reason, 'event' => 'payment_failed'];
    }
}

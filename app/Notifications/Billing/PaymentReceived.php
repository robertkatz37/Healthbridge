<?php

namespace App\Notifications\Billing;

use App\Models\Invoice;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'payment_received', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'payment_received',
            ['invoice_number' => $this->invoice->invoice_number, 'amount' => number_format((float) $this->invoice->total_amount, 2)],
            fn () => (new MailMessage)
                ->subject('Payment received — Invoice ' . $this->invoice->invoice_number)
                ->greeting('Hello,')
                ->line('We\'ve received your payment of $' . number_format((float) $this->invoice->total_amount, 2) . ' for invoice ' . $this->invoice->invoice_number . '.')
                ->action('View Invoice', route('agency.billing.invoices.show', $this->invoice))
                ->line('Thank you for your business.')
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['invoice_id' => $this->invoice->id, 'amount' => (string) $this->invoice->total_amount, 'event' => 'payment_received'];
    }
}

<?php

namespace App\Notifications\Billing;

use App\Models\Invoice;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGenerated extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'invoice_generated', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'invoice_generated',
            ['invoice_number' => $this->invoice->invoice_number, 'amount' => number_format((float) $this->invoice->total_amount, 2), 'due_date' => $this->invoice->due_date->format('M d, Y')],
            fn () => (new MailMessage)
                ->subject('New invoice — ' . $this->invoice->invoice_number)
                ->greeting('Hello,')
                ->line('A new invoice for $' . number_format((float) $this->invoice->total_amount, 2) . ' has been generated, due ' . $this->invoice->due_date->format('M d, Y') . '.')
                ->action('View Invoice', route('agency.billing.invoices.show', $this->invoice))
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['invoice_id' => $this->invoice->id, 'event' => 'invoice_generated'];
    }
}

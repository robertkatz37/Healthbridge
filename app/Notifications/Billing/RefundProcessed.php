<?php

namespace App\Notifications\Billing;

use App\Models\Refund;
use App\Notifications\Concerns\RendersFromEmailTemplate;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundProcessed extends Notification implements ShouldQueue
{
    use Queueable, RendersFromEmailTemplate;

    public function __construct(private readonly Refund $refund) {}

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->enabledChannels($notifiable, 'refund_processed', ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->renderMailMessage(
            'refund_processed',
            ['amount' => number_format((float) $this->refund->amount, 2), 'invoice_number' => $this->refund->invoice->invoice_number],
            fn () => (new MailMessage)
                ->subject('Your refund has been processed')
                ->greeting('Hello,')
                ->line('A refund of $' . number_format((float) $this->refund->amount, 2) . ' for invoice ' . $this->refund->invoice->invoice_number . ' has been processed.')
                ->line('It may take 5-10 business days to appear on your statement.')
                ->action('View Billing', route('agency.billing.index'))
        );
    }

    public function toArray(object $notifiable): array
    {
        return ['refund_id' => $this->refund->id, 'amount' => (string) $this->refund->amount, 'event' => 'refund_processed'];
    }
}

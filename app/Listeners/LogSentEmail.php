<?php

namespace App\Listeners;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;

/**
 * Logs every successfully sent email regardless of what triggered it
 * (email verification, password reset, Agency moderation notifications,
 * a future billing email, the "Send Test Email" button) — registered
 * against Laravel's own MessageSent event rather than instrumenting each
 * call site individually, so nothing can be sent without being logged.
 */
class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        $to = collect($event->message->getTo())
            ->map(fn ($address) => $address->getAddress())
            ->implode(', ');

        EmailLog::create([
            'to_address' => $to ?: 'unknown',
            'subject' => $event->message->getSubject(),
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}

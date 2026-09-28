<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent synchronously (no ShouldQueue) from the "Send Test Email" button —
 * an admin verifying SMTP credentials wants to know immediately whether
 * they work, not have the result buried in a queue worker's log.
 */
class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(private readonly string $platformName) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test Email from ' . $this->platformName,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>This is a test email from <strong>' . e($this->platformName) . '</strong>.</p>'
                . '<p>If you received this, your mail configuration is working correctly.</p>'
                . '<p style="color:#666;font-size:0.85em;">Sent at ' . now()->toDayDateTimeString() . '</p>',
        );
    }
}

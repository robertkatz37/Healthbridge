<?php

namespace App\Notifications\Concerns;

use App\Services\Settings\EmailTemplateService;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Shared by every Agency moderation notification (Phase 8) to check for
 * an active, Super-Admin-customized EmailTemplate before falling back to
 * the notification's own hardcoded MailMessage content — one place for
 * this "template if present, else fallback" logic rather than repeating
 * it in each of the 5 notification classes (DRY).
 */
trait RendersFromEmailTemplate
{
    protected function renderMailMessage(string $templateKey, array $variables, \Closure $fallback): MailMessage
    {
        $rendered = app(EmailTemplateService::class)->render($templateKey, $variables);

        if ($rendered === null) {
            return $fallback();
        }

        return (new MailMessage)
            ->subject($rendered['subject'])
            ->view('emails.template-body', ['bodyHtml' => $rendered['body']]);
    }
}

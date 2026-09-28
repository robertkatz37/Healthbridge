<?php

namespace App\Services\Settings;

use App\Models\EmailTemplate;

/**
 * Renders a stored EmailTemplate by key, substituting {{variable}}
 * placeholders. Returns null if no active template exists for that key —
 * callers (the Agency moderation Notifications) fall back to their own
 * hardcoded content in that case, so an inactive/missing template never
 * breaks an actual system email from sending.
 */
class EmailTemplateService
{
    public function render(string $key, array $variables = []): ?array
    {
        $template = EmailTemplate::active()->where('key', $key)->first();

        if (!$template) {
            return null;
        }

        return [
            'subject' => $this->substitute($template->subject, $variables),
            'body' => $this->substitute($template->body, $variables),
        ];
    }

    private function substitute(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string) $value, $text);
        }

        return $text;
    }
}

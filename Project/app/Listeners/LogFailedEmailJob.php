<?php

namespace App\Listeners;

use App\Models\EmailLog;
use Illuminate\Queue\Events\JobFailed;

/**
 * Mirrors queued-notification failures into the same email_logs table
 * used for successes (LogSentEmail), so the admin's "Email Logs" screen
 * is a single unified view rather than needing to also separately check
 * the raw failed_jobs table to understand what went wrong and to whom.
 * failed_jobs remains the source of truth for retry/delete actions (see
 * Admin\Settings\FailedEmailController) — this listener only adds a
 * human-readable log entry alongside it.
 *
 * Only logs jobs that are actually mail/notification-related (checked via
 * the resolved job class name) so unrelated queued job failures elsewhere
 * in the app don't pollute the Email Logs screen.
 */
class LogFailedEmailJob
{
    public function handle(JobFailed $event): void
    {
        $jobName = method_exists($event->job, 'resolveName')
            ? $event->job->resolveName()
            : get_class($event->job);

        $isMailRelated = str_contains($jobName, 'Notification') || str_contains($jobName, 'Mail');
        if (!$isMailRelated) {
            return;
        }

        EmailLog::create([
            'to_address' => 'unknown',
            'subject' => null,
            'status' => 'failed',
            'notification_class' => $jobName,
            'error_message' => substr($event->exception->getMessage(), 0, 1000),
            'sent_at' => now(),
        ]);
    }
}

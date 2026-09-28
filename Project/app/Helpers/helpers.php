<?php

if (!function_exists('activity')) {
    /**
     * Return a new ActivityLogger instance for fluent activity logging.
     * Signature mirrors spatie/laravel-activitylog's global helper so controllers
     * are compatible if the real package is installed in a future phase.
     */
    function activity(string $logName = 'default'): \App\Helpers\ActivityLogger
    {
        return (new \App\Helpers\ActivityLogger())->useLog($logName);
    }
}

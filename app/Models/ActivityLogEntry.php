<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Read model for the activity_log table, written to by the custom
 * App\Helpers\ActivityLogger shim (see its own docblock) rather than
 * the real Spatie Activitylog package, which isn't installed in this
 * project. Named ActivityLogEntry rather than "Activity" specifically
 * to avoid colliding with Spatie\Activitylog\Models\Activity if that
 * package is installed in a later phase — the table schema was already
 * built to be compatible with it.
 */
class ActivityLogEntry extends Model
{
    protected $table = 'activity_log';

    public $timestamps = true;

    protected $fillable = ['log_name', 'description', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'properties'];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}

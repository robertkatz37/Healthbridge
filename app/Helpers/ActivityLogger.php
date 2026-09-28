<?php

namespace App\Helpers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Lightweight activity logging helper that writes to the activity_log table.
 * Pre-built to match Spatie ActivityLog's schema so that if/when the package
 * is installed in a later phase, the data format is already compatible.
 * The fluent interface mirrors the Spatie API so controllers do not need changes
 * if the package is swapped in later.
 */
class ActivityLogger
{
    private ?Authenticatable $causer = null;
    private ?Model $subject = null;
    private array $properties = [];
    private string $logName = 'default';

    public function causedBy(?Authenticatable $causer): static
    {
        $this->causer = $causer;
        return $this;
    }

    public function performedOn(?Model $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function withProperties(array $properties): static
    {
        $this->properties = $properties;
        return $this;
    }

    public function useLog(string $logName): static
    {
        $this->logName = $logName;
        return $this;
    }

    public function log(string $description): void
    {
        try {
            \Illuminate\Support\Facades\DB::table('activity_log')->insert([
                'log_name'     => $this->logName,
                'description'  => $description,
                'subject_type' => $this->subject ? get_class($this->subject) : null,
                'subject_id'   => $this->subject?->getKey(),
                'causer_type'  => $this->causer ? get_class($this->causer) : null,
                'causer_id'    => $this->causer?->getAuthIdentifier(),
                'properties'   => json_encode($this->properties) ?: null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } catch (\Throwable) {
            // Never let logging break the application flow
        }
    }
}

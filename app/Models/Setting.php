<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'group', 'value', 'type', 'is_encrypted'];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    /**
     * Decrypts on read when is_encrypted is true. Safe to do as an
     * accessor (unlike the write side — see SettingsService::set() for
     * why encryption on write is handled there explicitly instead of via
     * a setValueAttribute() mutator): when a model is hydrated from a
     * database row, all attributes load simultaneously via
     * newFromBuilder(), not sequentially — so by the time this accessor
     * runs, is_encrypted is already populated correctly regardless of
     * column order. A mutator during mass-assignment (create()/fill())
     * does NOT have this guarantee: attributes are set one at a time in
     * whatever order the caller's array happens to list them, so a
     * setValueAttribute() mutator checking $this->is_encrypted could run
     * before is_encrypted itself was set, silently skipping encryption.
     * That exact bug was caught via smoke testing before shipping — see
     * DATABASE_DECISIONS.md.
     */
    public function getValueAttribute(?string $value): ?string
    {
        if ($value === null || !$this->is_encrypted) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            // Ciphertext from a different APP_KEY, or corrupted — fail
            // safe to null rather than throwing during a page render.
            return null;
        }
    }

    public function castValue(): mixed
    {
        return match ($this->type) {
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            'int' => (int) $this->value,
            default => $this->value,
        };
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}

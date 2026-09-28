<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'avatar',
        'last_login_at',
        'last_login_ip',
        'notification_preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'password'               => 'hashed',
            'two_factor_confirmed_at'=> 'datetime',
            'last_login_at'          => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    // ─── 2FA Helpers ──────────────────────────────────────────────────────────

    /**
     * Whether 2FA setup has been confirmed by the user scanning the QR code.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null
            && $this->two_factor_secret !== null;
    }

    /**
     * Whether 2FA is pending confirmation (secret generated but not confirmed).
     */
    public function hasTwoFactorPending(): bool
    {
        return $this->two_factor_secret !== null
            && $this->two_factor_confirmed_at === null;
    }

    /**
     * Decode stored recovery codes from JSON array.
     */
    public function getRecoveryCodesArray(): array
    {
        if (!$this->two_factor_recovery_codes) return [];
        return json_decode($this->two_factor_recovery_codes, true) ?? [];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function agency(): HasOne
    {
        return $this->hasOne(Agency::class);
    }

    public function agencyStaff(): HasMany
    {
        return $this->hasMany(AgencyStaff::class);
    }

    public function family(): HasOne
    {
        return $this->hasOne(Family::class);
    }

    public function advisor(): HasOne
    {
        return $this->hasOne(Advisor::class);
    }

    public function affiliate(): HasOne
    {
        return $this->hasOne(Affiliate::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    // ─── Agency Access Helper ─────────────────────────────────────────────────

    /**
     * Resolves the Agency this user is associated with, whether as the
     * Owner (agencies.user_id) or as Staff (agency_staff.user_id). Used
     * throughout the Agency Module (Phase 7) so controllers don't need to
     * branch on role — both agency_owner and agency_staff resolve here.
     * Returns null if the user has no agency association at all.
     *
     * Deliberately queries directly rather than reading $this->agency —
     * accessing a HasOne relation property lazy-loads and CACHES the
     * result (including a null "not found yet") on this object instance.
     * Since the same User instance is reused across multiple simulated
     * requests in feature tests (and can be reused within a single real
     * request too), a premature cached null would incorrectly persist
     * even after the Agency row is created later in the same lifecycle.
     */
    public function currentAgency(): ?Agency
    {
        $owned = Agency::where('user_id', $this->id)->first();
        if ($owned) {
            return $owned;
        }

        return AgencyStaff::where('user_id', $this->id)->with('agency')->first()?->agency;
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    // ─── Computed ─────────────────────────────────────────────────────────────

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        // Deterministic avatar using initials — no external service required
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&background=0B6E4F&color=fff&size=96";
    }
}

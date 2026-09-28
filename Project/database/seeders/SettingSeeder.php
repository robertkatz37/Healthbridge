<?php

namespace Database\Seeders;

use App\Services\Settings\SettingsService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SettingsService::class);

        // ─── General ────────────────────────────────────────────────────────
        $settings->set('platform_name', 'HealthsBridge', 'general', 'string');
        $settings->set('support_email', 'support@healthsbridge.com', 'general', 'string');
        $settings->set('support_phone', '', 'general', 'string');
        $settings->set('timezone', 'America/Chicago', 'general', 'string');
        $settings->set('maintenance_mode', 'false', 'general', 'bool');

        // ─── Branding ───────────────────────────────────────────────────────
        // from_name/from_email live here (not the mail group) per the
        // explicit Phase 10 requirement grouping "Branding settings
        // (Logo, Company Name, From Name, From Email)" — MailConfigService
        // reads these two specific keys from here when applying the
        // runtime mail 'from' header, so there's one source of truth
        // rather than duplicating from-address across two settings groups.
        $settings->set('branding_company_name', 'HealthsBridge', 'branding', 'string');
        $settings->set('branding_logo_path', '', 'branding', 'string');
        $settings->set('branding_from_name', 'HealthsBridge', 'branding', 'string');
        $settings->set('branding_from_email', 'hello@healthsbridge.test', 'branding', 'string');

        // ─── Mail / SMTP ────────────────────────────────────────────────────
        // Defaults to 'log' (writes to storage/logs/laravel.log instead of
        // actually sending) — the same safe default already in .env — so
        // a fresh install never silently attempts to reach a real SMTP
        // server with empty credentials. An admin switches this to 'smtp'
        // and fills in real credentials via the Mail Settings screen.
        $settings->set('mail_mailer', 'log', 'mail', 'string');
        $settings->set('mail_host', '', 'mail', 'string');
        $settings->set('mail_port', '587', 'mail', 'string');
        $settings->set('mail_username', '', 'mail', 'string');
        $settings->set('mail_password', '', 'mail', 'string', encrypted: true);
        $settings->set('mail_encryption', 'tls', 'mail', 'string');

        // ─── Legacy keys (pre-Phase-10, unrelated to mail — left as-is) ────────
        $settings->set('review_recency_window_months', '24', 'reviews', 'int');
        $settings->set('review_moderation_required', 'true', 'reviews', 'bool');
        $settings->set('default_commission_type', 'flat', 'commissions', 'string');
        $settings->set('default_commission_amount', '500.00', 'commissions', 'string');
        $settings->set('cookie_consent_enabled', 'true', 'general', 'bool');

        // ─── Matching Engine (Phase 12) ─────────────────────────────────────
        // Stored as JSON under one key rather than one settings row per
        // factor (~21 factors) — a single blob is simpler for
        // MatchingRuleEngine to load in one read, and the admin edits them
        // together on one form anyway. Weights are relative, not required
        // to sum to 100 — MatchingScoreService normalizes by the sum of
        // weights for factors that actually had determinable data for a
        // given Care Seeker/Agency pair, so a missing preference doesn't
        // silently penalize the score, it's just excluded from the pool.
        $settings->set('matching_weights', [
            'care_type' => 15,
            'location' => 8,
            'coverage_area' => 6,
            'distance' => 8,
            'budget' => 12,
            'services_offered' => 6,
            'languages' => 4,
            'insurance_accepted' => 5,
            'medicaid_medicare' => 4,
            'specialty_care' => 3,
            'memory_care' => 7,
            'mobility' => 5,
            'availability' => 4,
            'gender_preference' => 2,
            'veteran_benefits' => 3,
            'religious_preference' => 2,
            'pet_friendly' => 2,
            'accessibility' => 3,
            'review_rating' => 7,
            'agency_quality' => 3,
            'verification_status' => 2,
        ], 'matching', 'json');

        // Small, capped, additive bonus applied AFTER normalization — "a
        // small configurable bonus only," not a competing weighted factor
        // in the main pool, per the explicit Phase 12 requirement.
        $settings->set('matching_featured_boost', '3', 'matching', 'int');

        // Hard filter — agencies farther than this are excluded from
        // candidates entirely, not merely down-scored, since a
        // recommendation 300 miles away is not a valid recommendation
        // regardless of how well every other factor fits.
        $settings->set('matching_max_distance_miles', '100', 'matching', 'int');

        // Soft floor — recommendations below this score are computed and
        // stored (for advisor visibility/audit) but hidden from the
        // family-facing view by default. 0 = show everything.
        $settings->set('matching_min_score_threshold', '30', 'matching', 'int');
    }
}

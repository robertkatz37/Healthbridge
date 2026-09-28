<?php

namespace App\Enums;

/**
 * Agency moderation lifecycle (Phase 8). Expanded from the original 4-value
 * Phase 7 enum (Draft/PendingReview/Published/Suspended) to support the
 * full Admin moderation workflow per SRS §20:
 *
 *   Draft → PendingReview → Approved → Published
 *                 │
 *                 ├──→ ChangesRequested → (owner edits, resubmits) → PendingReview
 *                 └──→ Rejected (terminal)
 *
 *   Published → Suspended → Published (Reactivate, skips re-review)
 *
 * "Approve" is implemented as a single admin action that transitions
 * PendingReview → Approved → Published atomically (both steps recorded in
 * agency_status_history) — no separate manual "Publish" step exists,
 * since none was requested; see DATABASE_DECISIONS.md for the reasoning.
 * `Approved` therefore is not a resting state reachable via the UI, but
 * is retained as a real value (not skipped) so "only Approved agencies
 * may become Published" is enforced structurally, not just by convention,
 * and so a future phase (e.g. billing-gated go-live) can reintroduce a
 * genuine pause between the two without a schema change.
 */
enum AgencyStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Published = 'published';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending Review',
            self::ChangesRequested => 'Changes Requested',
            self::Approved => 'Approved',
            self::Published => 'Published',
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    /**
     * Valid forward transitions, enforced by AgencyModerationService —
     * mirrors the ReferralStatus::allowedNextStatuses() pattern.
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::Draft => [self::PendingReview],
            self::PendingReview => [self::Approved, self::Rejected, self::ChangesRequested],
            self::ChangesRequested => [self::PendingReview],
            self::Approved => [self::Published],
            self::Published => [self::Suspended],
            self::Suspended => [self::Published],
            self::Rejected => [],
        };
    }

    /**
     * Statuses considered "still in the moderation queue" — used to build
     * the Admin Applications Queue's default filter.
     */
    public static function queueStatuses(): array
    {
        return [self::PendingReview, self::ChangesRequested];
    }
}

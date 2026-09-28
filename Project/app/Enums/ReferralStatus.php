<?php

namespace App\Enums;

/**
 * Rewritten for Phase 13 with the full 11-value workflow (was a 7-value
 * enum from Phase 2 that never had any real usage — see
 * DATABASE_DECISIONS.md for why this was safe to replace outright rather
 * than migrate).
 *
 * State machine philosophy matches LeadStatus (Phase 11/12): at most one
 * "forward progress" option is ever shown at a time, with exactly one
 * genuine fork (Agency Accepted vs Agency Declined, since that's an
 * actual binary decision the agency makes, not the advisor skipping
 * around). "Cancel" and "Close as Lost" are handled as separate
 * escape-hatch actions (canCancel()/canCloseAsLost()), not mixed into
 * allowedNextStatuses() — this is the exact fix applied to LeadStatus
 * after a reported bug where multiple simultaneous "next stage" buttons
 * let advisors skip required stages. See DATABASE_DECISIONS.md.
 */
enum ReferralStatus: string
{
    case Pending = 'pending';
    case SentToAgency = 'sent_to_agency';
    case AgencyAccepted = 'agency_accepted';
    case AgencyDeclined = 'agency_declined';
    case TourScheduled = 'tour_scheduled';
    case TourCompleted = 'tour_completed';
    case FollowUpRequired = 'follow_up_required';
    case MoveInConfirmed = 'move_in_confirmed';
    case Converted = 'converted';
    case ClosedLost = 'closed_lost';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::SentToAgency => 'Sent to Agency',
            self::AgencyAccepted => 'Agency Accepted',
            self::AgencyDeclined => 'Agency Declined',
            self::TourScheduled => 'Tour Scheduled',
            self::TourCompleted => 'Tour Completed',
            self::FollowUpRequired => 'Follow-up Required',
            self::MoveInConfirmed => 'Move-In Confirmed',
            self::Converted => 'Converted',
            self::ClosedLost => 'Closed Lost',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * The single (or, at the Agency's decision point, the two genuinely
     * forked) valid forward transition(s). Never more than one option
     * from any stage where the advisor is the one choosing.
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::Pending => [self::SentToAgency],
            self::SentToAgency => [self::AgencyAccepted, self::AgencyDeclined],
            self::AgencyAccepted => [self::TourScheduled],
            self::TourScheduled => [self::TourCompleted],
            self::TourCompleted => [self::FollowUpRequired],
            self::FollowUpRequired => [self::MoveInConfirmed],
            self::MoveInConfirmed => [self::Converted],
            self::Converted, self::ClosedLost, self::Cancelled, self::AgencyDeclined => [],
        };
    }

    /**
     * True for every stage before the agency has been engaged — the
     * advisor withdrawing a referral before/while it's out for
     * consideration. Kept separate from allowedNextStatuses() so it
     * renders as a distinct action, not another "next stage" option.
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::Pending, self::SentToAgency], true);
    }

    /**
     * True once the agency has actually engaged (accepted) through to
     * the final decision point — the family/advisor deciding this
     * particular agency isn't going to work out, at any point after
     * real engagement began. "Closed Lost" before the agency even
     * responds isn't a distinct outcome from "Cancelled".
     */
    public function canCloseAsLost(): bool
    {
        return in_array($this, [
            self::AgencyAccepted, self::TourScheduled, self::TourCompleted, self::FollowUpRequired,
        ], true);
    }

    public function isOpen(): bool
    {
        return !in_array($this, [self::Converted, self::ClosedLost, self::Cancelled, self::AgencyDeclined], true);
    }

    public static function openStatuses(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => $s->isOpen()));
    }

    public function badgeColor(): array
    {
        return match ($this) {
            self::Pending => ['bg' => 'var(--hb-gray-200)', 'text' => 'var(--hb-gray-600)'],
            self::SentToAgency, self::AgencyAccepted => ['bg' => '#EFF6FF', 'text' => '#1E40AF'],
            self::AgencyDeclined, self::ClosedLost, self::Cancelled => ['bg' => '#FEF2F2', 'text' => '#991B1B'],
            self::TourScheduled, self::TourCompleted, self::FollowUpRequired => ['bg' => '#FFFBEB', 'text' => '#92400E'],
            self::MoveInConfirmed, self::Converted => ['bg' => 'var(--hb-emerald-100)', 'text' => 'var(--hb-emerald-700)'],
        };
    }
}

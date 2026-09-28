<?php

namespace App\Enums;

/**
 * The Advisor CRM pipeline (Phase 11) — distinct from ReferralStatus
 * (Phase 2/14), which tracks a specific Family-to-Agency introduction.
 * A Lead is the advisor's own case-tracking for a Family across their
 * entire care search, which may eventually touch several agencies/
 * referrals. State machine mirrors the AgencyStatus/ReferralStatus
 * pattern used throughout this project.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case Contacted = 'contacted';
    case AssessmentReviewed = 'assessment_reviewed';
    case Shortlisted = 'shortlisted';
    case TourScheduled = 'tour_scheduled';
    case FollowUp = 'follow_up';
    case MoveInConfirmed = 'move_in_confirmed';
    case Converted = 'converted';
    case ClosedLost = 'closed_lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Assigned => 'Assigned',
            self::Contacted => 'Contacted',
            self::AssessmentReviewed => 'Assessment Reviewed',
            self::Shortlisted => 'Shortlisted',
            self::TourScheduled => 'Tour Scheduled',
            self::FollowUp => 'Follow-up',
            self::MoveInConfirmed => 'Move-In Confirmed',
            self::Converted => 'Converted',
            self::ClosedLost => 'Closed Lost',
        };
    }

    /**
     * The single valid forward "progress" transition for each stage,
     * enforced by LeadPipelineService — a strict linear sequence (New →
     * Assigned → Contacted → Assessment Reviewed → Shortlisted → Tour
     * Scheduled → Follow-up → Move-In Confirmed → Converted). An advisor
     * is never shown more than one "Move to X" progression option at a
     * time; skipping a required stage is not possible.
     *
     * "Closed Lost" is deliberately NOT included here even though it's a
     * valid transition from every open stage — it's handled by
     * canCloseAsLost() below and rendered as a visually distinct action
     * in the UI (a family can drop out of the process at any point,
     * which is a different kind of action than progressing forward, not
     * an equally-weighted alternative "next stage"). Prior to this fix,
     * ClosedLost was mixed into this same list alongside the real
     * forward option(s), and FollowUp was reachable as a re-entrant
     * "resume from anywhere" state from five different stages — both
     * together meant an advisor could see 2-3 "next stage" buttons
     * simultaneously after a single transition, which is exactly the
     * reported bug. See DATABASE_DECISIONS.md.
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::New => [self::Assigned],
            self::Assigned => [self::Contacted],
            self::Contacted => [self::AssessmentReviewed],
            self::AssessmentReviewed => [self::Shortlisted],
            self::Shortlisted => [self::TourScheduled],
            self::TourScheduled => [self::FollowUp],
            self::FollowUp => [self::MoveInConfirmed],
            self::MoveInConfirmed => [self::Converted],
            self::Converted => [],
            self::ClosedLost => [],
        };
    }

    /**
     * True for every open (non-terminal) stage — a lead can be closed as
     * lost from anywhere in the pipeline, since a family can decide not
     * to proceed at any point. Kept separate from allowedNextStatuses()
     * so the UI can render it as a distinct, differently-styled action
     * (e.g. a small "Close as Lost" link, not another "Move to X"
     * progression button) rather than one more equally-weighted forward
     * option.
     */
    public function canCloseAsLost(): bool
    {
        return $this->isOpen();
    }

    public function isOpen(): bool
    {
        return !in_array($this, [self::Converted, self::ClosedLost], true);
    }

    public static function openStatuses(): array
    {
        return array_filter(self::cases(), fn (self $s) => $s->isOpen());
    }
}

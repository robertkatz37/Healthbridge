<?php

namespace App\Services\Referral;

use App\Enums\ReferralPriority;
use App\Enums\ReferralSource;
use App\Enums\ReferralStatus;
use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Lead;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The bridge between Phase 12 (Matching Engine) and Phase 13 (Referral
 * workflow). "Build a shortlist" (an advisor curating candidate
 * agencies) already exists as MatchResult::is_advisor_approved, Phase
 * 12 — nothing new needed for that step. "Send Referral" is the action
 * that actually creates a formal Referral record and moves it straight
 * to SentToAgency in one atomic advisor action, since a Referral only
 * comes into existence at the moment it's sent (there's no separate
 * "draft referral" state — the shortlist itself is the draft).
 */
class ReferralCreationService
{
    public function __construct(
        private readonly ReferralPipelineService $pipeline,
    ) {}

    public function sendReferral(
        Lead $lead,
        Agency $agency,
        Advisor $advisor,
        ?User $actor = null,
        ReferralPriority $priority = ReferralPriority::Medium,
        ?string $notes = null,
    ): Referral {
        $this->assertNoOpenReferralExists($lead, $agency);

        $referral = Referral::create([
            'family_id' => $lead->family_id,
            'care_seeker_id' => $lead->care_seeker_id,
            'agency_id' => $agency->id,
            'advisor_id' => $advisor->id,
            'lead_id' => $lead->id,
            'priority' => $priority->value,
            'source' => ReferralSource::MatchingEngine->value,
        ]);

        if ($notes) {
            $referral->notes()->create([
                'author_id' => $actor?->id ?? $advisor->user_id,
                'author_type' => 'advisor',
                'visible_to_agency' => true,
                'content' => $notes,
            ]);
        }

        $this->pipeline->transition($referral, ReferralStatus::SentToAgency, $actor);

        return $referral;
    }

    /**
     * "Resend referrals if necessary" — creates a fresh Referral (a new
     * row, since a Referral's identity is tied to one send attempt) for
     * the same Lead/Agency pair, only once any prior attempt to that
     * same agency has reached a terminal state. Prevents two
     * simultaneously "open" referrals to the same agency for the same
     * lead, which would be a confusing duplicate for the agency to see
     * in their inbox.
     */
    private function assertNoOpenReferralExists(Lead $lead, Agency $agency): void
    {
        $openExists = Referral::where('lead_id', $lead->id)
            ->where('agency_id', $agency->id)
            ->open()
            ->exists();

        if ($openExists) {
            throw ValidationException::withMessages([
                'agency_id' => 'A referral to this agency is already in progress for this lead.',
            ]);
        }
    }
}

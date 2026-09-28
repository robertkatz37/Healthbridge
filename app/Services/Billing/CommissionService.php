<?php

namespace App\Services\Billing;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\InvoiceType;
use App\Models\Advisor;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Referral;
use App\Models\User;

/**
 * Commission Foundation: computes and records what's owed on a
 * completed referral — the existing platform referral_fee (built
 * ahead of this phase) plus, new in Phase 16, an advisor_commission
 * when the referral has an assigned advisor. Affiliate commissions
 * are deliberately not computed here yet ("future phase" per the
 * brief) — commission_type exists on both tables already so that
 * work only needs a new CommissionRule row and a call to
 * recordAdvisorCommission()'s sibling, not a schema change.
 */
class CommissionService
{
    /**
     * Called when a referral converts (see ReferralStatusService)
     * — computes the platform's referral fee against the
     * platform-default (or agency-specific override) rule.
     */
    public function recordReferralFee(Referral $referral): ?Commission
    {
        $rule = $this->ruleFor($referral, CommissionType::ReferralFee);
        if (!$rule) {
            return null;
        }

        return Commission::create([
            'referral_id' => $referral->id,
            'commission_rule_id' => $rule->id,
            'commission_type' => CommissionType::ReferralFee->value,
            'amount' => $rule->computeAmount(),
            'status' => CommissionStatus::Due->value,
        ]);
    }

    /**
     * Advisor commission on the same converted referral — a separate
     * Commission row with its own rule and beneficiary (the Advisor —
     * referrals.advisor_id references the advisors table, a distinct
     * model from User with its own territory/rate profile, not a User
     * directly), so the two payouts (platform fee vs advisor payout)
     * are tracked and invoiced independently even though they're both
     * triggered by the same event.
     */
    public function recordAdvisorCommission(Referral $referral, Advisor $advisor): ?Commission
    {
        $rule = $this->ruleFor($referral, CommissionType::AdvisorCommission);
        if (!$rule) {
            return null;
        }

        return Commission::create([
            'referral_id' => $referral->id,
            'commission_rule_id' => $rule->id,
            'commission_type' => CommissionType::AdvisorCommission->value,
            'amount' => $rule->computeAmount(),
            'status' => CommissionStatus::Due->value,
            'beneficiary_type' => Advisor::class,
            'beneficiary_id' => $advisor->id,
        ]);
    }

    /**
     * Rule resolution order: an agency-specific override for this
     * commission_type first, falling back to the platform default
     * (agency_id null) — matches the existing CommissionRule design
     * from before this phase (scopePlatformDefault).
     */
    private function ruleFor(Referral $referral, CommissionType $type): ?CommissionRule
    {
        $agencyId = $referral->agency_id;

        return CommissionRule::active()->ofType($type)
            ->where('agency_id', $agencyId)->first()
            ?? CommissionRule::active()->ofType($type)->platformDefault()->first();
    }

    public function markInvoiced(Commission $commission): Commission
    {
        $commission->update(['status' => CommissionStatus::Invoiced->value]);

        return $commission->fresh();
    }

    public function markPaid(Commission $commission): Commission
    {
        $commission->update(['status' => CommissionStatus::Paid->value]);

        return $commission->fresh();
    }
}

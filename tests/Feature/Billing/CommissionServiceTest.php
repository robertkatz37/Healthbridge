<?php

use App\Enums\CommissionType;
use App\Enums\ReferralStatus;
use App\Models\Advisor;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Referral;
use App\Services\Referral\ReferralPipelineService;

test('converting a referral records the platform referral fee using the platform default rule', function () {
    $referral = Referral::factory()->create(['status' => ReferralStatus::MoveInConfirmed->value]);

    app(ReferralPipelineService::class)->transition($referral, ReferralStatus::Converted);

    $commission = Commission::where('referral_id', $referral->id)->where('commission_type', CommissionType::ReferralFee->value)->first();
    expect($commission)->not->toBeNull();
    expect((float) $commission->amount)->toBe(500.00);
    expect($commission->status->value)->toBe('due');
});

test('an agency-specific commission rule overrides the platform default', function () {
    $referral = Referral::factory()->create(['status' => ReferralStatus::MoveInConfirmed->value]);
    CommissionRule::create(['agency_id' => $referral->agency_id, 'rule_type' => 'flat', 'commission_type' => 'referral_fee', 'amount' => 750, 'is_active' => true]);

    app(ReferralPipelineService::class)->transition($referral, ReferralStatus::Converted);

    $commission = Commission::where('referral_id', $referral->id)->where('commission_type', CommissionType::ReferralFee->value)->first();
    expect((float) $commission->amount)->toBe(750.00);
});

test('converting a referral with an assigned advisor also records an advisor commission with the advisor as beneficiary', function () {
    $advisor = Advisor::factory()->create();
    $referral = Referral::factory()->create(['status' => ReferralStatus::MoveInConfirmed->value, 'advisor_id' => $advisor->id]);
    CommissionRule::create(['agency_id' => null, 'rule_type' => 'flat', 'commission_type' => 'advisor_commission', 'amount' => 150, 'is_active' => true]);

    app(ReferralPipelineService::class)->transition($referral, ReferralStatus::Converted);

    $advisorCommission = Commission::where('referral_id', $referral->id)->where('commission_type', CommissionType::AdvisorCommission->value)->first();
    expect($advisorCommission)->not->toBeNull();
    expect((float) $advisorCommission->amount)->toBe(150.00);
    expect($advisorCommission->beneficiary_type)->toBe(Advisor::class);
    expect($advisorCommission->beneficiary_id)->toBe($advisor->id);
});

test('converting a referral with no assigned advisor does not create an advisor commission', function () {
    $referral = Referral::factory()->create(['status' => ReferralStatus::MoveInConfirmed->value, 'advisor_id' => null]);

    app(ReferralPipelineService::class)->transition($referral, ReferralStatus::Converted);

    expect(Commission::where('referral_id', $referral->id)->where('commission_type', CommissionType::AdvisorCommission->value)->exists())->toBeFalse();
});

test('a percentage-type commission rule computes its amount from a base value', function () {
    $rule = CommissionRule::create(['agency_id' => null, 'rule_type' => 'percentage', 'commission_type' => 'advisor_commission', 'amount' => 10, 'is_active' => true]);

    expect($rule->computeAmount(1000))->toBe(100.0);
});

test('an inactive commission rule is not used for computation', function () {
    $referral = Referral::factory()->create(['status' => ReferralStatus::MoveInConfirmed->value]);
    CommissionRule::create(['agency_id' => $referral->agency_id, 'rule_type' => 'flat', 'commission_type' => 'referral_fee', 'amount' => 999, 'is_active' => false]);

    app(ReferralPipelineService::class)->transition($referral, ReferralStatus::Converted);

    $commission = Commission::where('referral_id', $referral->id)->where('commission_type', CommissionType::ReferralFee->value)->first();
    expect((float) $commission->amount)->toBe(500.00);
});

<?php

use App\Enums\LeadStatus;
use App\Models\Family;
use App\Models\User;
use App\Services\Advisor\LeadPipelineService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->withoutVite();
    $this->service = app(LeadPipelineService::class);

    $familyUser = User::factory()->create();
    $family = Family::create(['user_id' => $familyUser->id]);
    $this->lead = $family->leads()->create(['status' => 'assigned', 'source' => 'manual']);
});

test('legal transition succeeds and updates status', function () {
    $this->service->transition($this->lead, LeadStatus::Contacted);

    expect($this->lead->fresh()->status)->toBe(LeadStatus::Contacted);
});

test('illegal transition throws a validation exception', function () {
    expect(fn () => $this->service->transition($this->lead, LeadStatus::Converted))
        ->toThrow(ValidationException::class);

    expect($this->lead->fresh()->status)->toBe(LeadStatus::Assigned);
});

test('every transition writes a status history row', function () {
    $this->service->transition($this->lead, LeadStatus::Contacted);
    $this->service->transition($this->lead, LeadStatus::AssessmentReviewed);

    expect($this->lead->statusHistory()->count())->toBe(2);
});

test('converting a lead stamps converted_at', function () {
    $this->service->transition($this->lead, LeadStatus::Contacted);
    $this->service->transition($this->lead, LeadStatus::AssessmentReviewed);
    $this->service->transition($this->lead, LeadStatus::Shortlisted);
    $this->service->transition($this->lead, LeadStatus::TourScheduled);
    $this->service->transition($this->lead, LeadStatus::FollowUp);
    $this->service->transition($this->lead, LeadStatus::MoveInConfirmed);
    $this->service->transition($this->lead, LeadStatus::Converted);

    $this->lead->refresh();
    expect($this->lead->status)->toBe(LeadStatus::Converted);
    expect($this->lead->converted_at)->not->toBeNull();
});

test('closing a lead as lost requires and stores a reason', function () {
    $this->service->transition($this->lead, LeadStatus::ClosedLost, null, 'Chose another provider');

    $this->lead->refresh();
    expect($this->lead->status)->toBe(LeadStatus::ClosedLost);
    expect($this->lead->closed_at)->not->toBeNull();
    expect($this->lead->closed_reason)->toBe('Chose another provider');
});

test('converted is a terminal status', function () {
    expect(LeadStatus::Converted->allowedNextStatuses())->toBe([]);
    expect(LeadStatus::Converted->isOpen())->toBeFalse();
});

test('closed_lost is a terminal status', function () {
    expect(LeadStatus::ClosedLost->allowedNextStatuses())->toBe([]);
    expect(LeadStatus::ClosedLost->isOpen())->toBeFalse();
});

test('each open stage has exactly one forward progression option, never more', function () {
    foreach (LeadStatus::cases() as $status) {
        if (!$status->isOpen()) {
            continue;
        }
        expect(count($status->allowedNextStatuses()))->toBeLessThanOrEqual(1);
    }
});

test('the pipeline follows the exact specified linear sequence with no skipped stages', function () {
    $sequence = [
        LeadStatus::New, LeadStatus::Assigned, LeadStatus::Contacted, LeadStatus::AssessmentReviewed,
        LeadStatus::Shortlisted, LeadStatus::TourScheduled, LeadStatus::FollowUp,
        LeadStatus::MoveInConfirmed, LeadStatus::Converted,
    ];

    for ($i = 0; $i < count($sequence) - 1; $i++) {
        expect($sequence[$i]->allowedNextStatuses())->toBe([$sequence[$i + 1]]);
    }
});

test('closed_lost is reachable from every open stage via canCloseAsLost, but is never listed in allowedNextStatuses', function () {
    foreach (LeadStatus::cases() as $status) {
        if ($status->isOpen()) {
            expect($status->canCloseAsLost())->toBeTrue();
        } else {
            expect($status->canCloseAsLost())->toBeFalse();
        }
        expect($status->allowedNextStatuses())->not->toContain(LeadStatus::ClosedLost);
    }
});

test('a lead can be closed as lost directly from Contacted, skipping the rest of the pipeline, via the dedicated escape hatch', function () {
    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'contacted', 'source' => 'manual']);

    app(\App\Services\Advisor\LeadPipelineService::class)->transition($lead, LeadStatus::ClosedLost, null, 'Family chose another provider');

    expect($lead->fresh()->status)->toBe(LeadStatus::ClosedLost);
});

test('openStatuses excludes converted and closed_lost', function () {
    $open = LeadStatus::openStatuses();

    expect($open)->not->toContain(LeadStatus::Converted);
    expect($open)->not->toContain(LeadStatus::ClosedLost);
    expect($open)->toContain(LeadStatus::New);
});

test('REGRESSION: after moving to Contacted, only one Move to X button is available, not multiple simultaneous options', function () {
    // Reproduces the exact reported bug: after "Move to Contacted", the
    // advisor saw "Move to Assessment Reviewed", "Move to Follow-up",
    // AND "Closed Lost" simultaneously, letting stages be skipped.
    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'contacted', 'source' => 'manual']);

    $nextStatuses = $lead->status->allowedNextStatuses();

    expect($nextStatuses)->toHaveCount(1);
    expect($nextStatuses[0])->toBe(LeadStatus::AssessmentReviewed);
    expect($nextStatuses)->not->toContain(LeadStatus::FollowUp);
});

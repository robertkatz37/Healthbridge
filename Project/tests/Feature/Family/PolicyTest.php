<?php

use App\Models\CareSeeker;
use App\Models\Family;
use App\Models\FamilyNote;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

// ─── FamilyPolicy ───────────────────────────────────────────────────────────

test('family user can view their own family record', function () {
    $user = User::factory()->create()->assignRole('family');
    $family = Family::create(['user_id' => $user->id]);

    expect($user->can('view', $family))->toBeTrue();
});

test('family user cannot view another familys record', function () {
    $user = User::factory()->create()->assignRole('family');
    Family::create(['user_id' => $user->id]);
    $otherFamily = Family::factory()->create();

    expect($user->can('view', $otherFamily))->toBeFalse();
});

test('super_admin can view any family record', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $family = Family::factory()->create();

    expect($admin->can('view', $family))->toBeTrue();
});

// ─── CareSeekerPolicy ───────────────────────────────────────────────────────

test('family user can view their own care seeker', function () {
    $user = User::factory()->create()->assignRole('family');
    $family = Family::create(['user_id' => $user->id]);
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    expect($user->can('view', $careSeeker))->toBeTrue();
    expect($user->can('update', $careSeeker))->toBeTrue();
    expect($user->can('delete', $careSeeker))->toBeTrue();
});

test('family user cannot view a care seeker belonging to another family', function () {
    $user = User::factory()->create()->assignRole('family');
    Family::create(['user_id' => $user->id]);

    $otherFamily = Family::factory()->create();
    $otherCareSeeker = $otherFamily->careSeekers()->create(['first_name' => 'Not', 'last_name' => 'Yours']);

    expect($user->can('view', $otherCareSeeker))->toBeFalse();
    expect($user->can('update', $otherCareSeeker))->toBeFalse();
    expect($user->can('delete', $otherCareSeeker))->toBeFalse();
});

test('non-family role cannot create a care seeker', function () {
    $agencyOwner = User::factory()->create()->assignRole('agency_owner');

    expect($agencyOwner->can('create', CareSeeker::class))->toBeFalse();
});

test('super_admin can view any care seeker', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $careSeeker = CareSeeker::factory()->create();

    expect($admin->can('view', $careSeeker))->toBeTrue();
});

// ─── FamilyNotePolicy ───────────────────────────────────────────────────────

test('family user can manage their own notes', function () {
    $user = User::factory()->create()->assignRole('family');
    $family = Family::create(['user_id' => $user->id]);
    $note = $family->notes()->create(['note' => 'Test']);

    expect($user->can('view', $note))->toBeTrue();
    expect($user->can('delete', $note))->toBeTrue();
});

test('family user cannot manage another familys notes', function () {
    $user = User::factory()->create()->assignRole('family');
    Family::create(['user_id' => $user->id]);

    $otherFamily = Family::factory()->create();
    $otherNote = $otherFamily->notes()->create(['note' => 'Not yours']);

    expect($user->can('view', $otherNote))->toBeFalse();
    expect($user->can('delete', $otherNote))->toBeFalse();
});

// ─── CareSeekerDocumentPolicy ───────────────────────────────────────────────

test('family user can view their own care seeker documents', function () {
    $user = User::factory()->create()->assignRole('family');
    $family = Family::create(['user_id' => $user->id]);
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $document = $careSeeker->documents()->create([
        'uploaded_by' => $user->id,
        'document_type' => 'medical_record',
        'path' => 'test.pdf',
        'original_filename' => 'test.pdf',
    ]);

    expect($user->can('view', $document))->toBeTrue();
    expect($user->can('delete', $document))->toBeTrue();
});

test('family user cannot view documents belonging to another familys care seeker', function () {
    $user = User::factory()->create()->assignRole('family');
    Family::create(['user_id' => $user->id]);

    $otherFamily = Family::factory()->create();
    $otherCareSeeker = $otherFamily->careSeekers()->create(['first_name' => 'Not', 'last_name' => 'Yours']);
    $document = $otherCareSeeker->documents()->create([
        'uploaded_by' => $otherFamily->user_id,
        'document_type' => 'medical_record',
        'path' => 'test.pdf',
        'original_filename' => 'test.pdf',
    ]);

    expect($user->can('view', $document))->toBeFalse();
    expect($user->can('delete', $document))->toBeFalse();
});

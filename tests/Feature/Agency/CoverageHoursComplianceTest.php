<?php

use App\Models\AgencyCategory;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->owner = User::factory()->create();
    $this->owner->assignRole('agency_owner');
    $category = AgencyCategory::first();

    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, [
        'agency_category_id' => $category->id,
        'name' => 'Test Agency',
    ]);
    $this->agency->update(['status' => 'published', 'onboarding_completed_at' => now()]);
});

// ─── Coverage ─────────────────────────────────────────────────────────────────

test('agency owner can add a coverage area', function () {
    $response = $this->actingAs($this->owner)->post(route('agency.coverage.store'), [
        'city' => 'Austin', 'state' => 'TX', 'radius_miles' => 30,
    ]);

    $response->assertRedirect();
    expect($this->agency->coverage()->count())->toBe(1);
});

test('agency owner can remove a coverage area', function () {
    $coverage = $this->agency->coverage()->create(['city' => 'Austin', 'state' => 'TX']);

    $response = $this->actingAs($this->owner)->delete(route('agency.coverage.destroy', $coverage));

    $response->assertRedirect();
    expect($this->agency->coverage()->count())->toBe(0);
});

// ─── Hours ────────────────────────────────────────────────────────────────────

test('agency owner can update weekly business hours', function () {
    $days = [];
    for ($i = 0; $i < 7; $i++) {
        $days[$i] = $i === 0
            ? ['is_closed' => '1']
            : ['open_time' => '09:00', 'close_time' => '17:00'];
    }

    $response = $this->actingAs($this->owner)->put(route('agency.hours.update'), ['days' => $days]);

    $response->assertRedirect();
    expect($this->agency->hours()->count())->toBe(7);
    expect($this->agency->hours()->where('day_of_week', 0)->first()->is_closed)->toBeTrue();
});

// ─── Certifications ───────────────────────────────────────────────────────────

test('agency owner can add a certification without a document', function () {
    $response = $this->actingAs($this->owner)->post(route('agency.certifications.store'), [
        'name' => 'State License',
        'issuing_body' => 'Texas DHS',
    ]);

    $response->assertRedirect();
    expect($this->agency->certifications()->count())->toBe(1);
});

test('agency owner can remove a certification', function () {
    $cert = $this->agency->certifications()->create(['name' => 'State License']);

    $response = $this->actingAs($this->owner)->delete(route('agency.certifications.destroy', $cert));

    $response->assertRedirect();
    expect($this->agency->certifications()->count())->toBe(0);
});

// ─── Documents (private storage) ───────────────────────────────────────────────

test('agency owner can upload a private insurance document', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('insurance.pdf', 500, 'application/pdf');

    $response = $this->actingAs($this->owner)->post(route('agency.documents.store'), [
        'document_type' => 'insurance',
        'file' => $file,
    ]);

    $response->assertRedirect();
    expect($this->agency->documents()->count())->toBe(1);

    $document = $this->agency->documents()->first();
    Storage::disk('local')->assertExists($document->path);
});

test('another agency owner cannot download a private document that is not theirs', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('insurance.pdf', 500, 'application/pdf');
    $this->actingAs($this->owner)->post(route('agency.documents.store'), [
        'document_type' => 'insurance', 'file' => $file,
    ]);
    $document = $this->agency->documents()->first();

    $otherOwner = User::factory()->create()->assignRole('agency_owner');
    \App\Models\Agency::factory()->create(['user_id' => $otherOwner->id]);

    $response = $this->actingAs($otherOwner)->get(route('agency.documents.download', $document));

    $response->assertStatus(403);
});

test('agency owner can delete a document', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('insurance.pdf', 500, 'application/pdf');
    $this->actingAs($this->owner)->post(route('agency.documents.store'), [
        'document_type' => 'insurance', 'file' => $file,
    ]);
    $document = $this->agency->documents()->first();

    $response = $this->actingAs($this->owner)->delete(route('agency.documents.destroy', $document));

    $response->assertRedirect();
    expect($this->agency->documents()->count())->toBe(0);
});

// ─── Media (public storage) ────────────────────────────────────────────────────

test('agency owner can upload a photo to the media gallery', function () {
    Storage::fake('public');

    $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $tmpPath = sys_get_temp_dir() . '/test_media_' . uniqid() . '.png';
    file_put_contents($tmpPath, $pngBytes);
    $file = new \Illuminate\Http\UploadedFile($tmpPath, 'photo.png', 'image/png', null, true);

    $response = $this->actingAs($this->owner)->post(route('agency.media.store'), [
        'type' => 'photo',
        'file' => $file,
        'caption' => 'Front entrance',
    ]);

    @unlink($tmpPath);

    $response->assertRedirect();
    expect($this->agency->media()->count())->toBe(1);
});

test('agency owner can add a video via external url', function () {
    $response = $this->actingAs($this->owner)->post(route('agency.media.store'), [
        'type' => 'video',
        'url' => 'https://youtube.com/watch?v=example',
        'caption' => 'Facility tour',
    ]);

    $response->assertRedirect();
    $media = $this->agency->media()->first();
    expect($media->type->value)->toBe('video');
    expect($media->path)->toBe('https://youtube.com/watch?v=example');
});

<?php

use App\Models\Family;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create()->assignRole('family');
    $this->family = Family::create(['user_id' => $this->user->id]);
    $this->careSeeker = $this->family->careSeekers()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
});

// ─── Family Notes ───────────────────────────────────────────────────────────

test('family can add a general note', function () {
    $response = $this->actingAs($this->user)->post(route('family.notes.store'), [
        'note' => 'Ask about visiting hours at Sunrise Manor.',
    ]);

    $response->assertRedirect();
    expect($this->family->notes()->count())->toBe(1);
});

test('family can add a note tied to a specific care seeker', function () {
    $response = $this->actingAs($this->user)->post(route('family.notes.store'), [
        'care_seeker_id' => $this->careSeeker->id,
        'note' => 'Mom prefers a place with a garden.',
    ]);

    $response->assertRedirect();
    $note = $this->family->notes()->first();
    expect($note->care_seeker_id)->toBe($this->careSeeker->id);
});

test('family can delete their own note', function () {
    $note = $this->family->notes()->create(['note' => 'Test note']);

    $response = $this->actingAs($this->user)->delete(route('family.notes.destroy', $note));

    $response->assertRedirect();
    expect($this->family->notes()->count())->toBe(0);
});

test('family cannot delete another familys note', function () {
    $otherFamily = Family::factory()->create();
    $otherNote = $otherFamily->notes()->create(['note' => 'Not yours']);

    $response = $this->actingAs($this->user)->delete(route('family.notes.destroy', $otherNote));

    $response->assertStatus(403);
});

// ─── Care Seeker Documents ──────────────────────────────────────────────────

test('family can upload a private document for a care seeker', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('medical.pdf', 500, 'application/pdf');

    $response = $this->actingAs($this->user)->post(
        route('family.care-seekers.documents.store', $this->careSeeker),
        ['document_type' => 'medical_record', 'file' => $file]
    );

    $response->assertRedirect();
    expect($this->careSeeker->documents()->count())->toBe(1);
    Storage::disk('local')->assertExists($this->careSeeker->documents()->first()->path);
});

test('family can download their own care seeker document', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('medical.pdf', 500, 'application/pdf');
    $this->actingAs($this->user)->post(
        route('family.care-seekers.documents.store', $this->careSeeker),
        ['document_type' => 'medical_record', 'file' => $file]
    );
    $document = $this->careSeeker->documents()->first();

    $response = $this->actingAs($this->user)->get(
        route('family.care-seekers.documents.download', [$this->careSeeker, $document])
    );

    $response->assertOk();
});

test('another family cannot download a document that is not theirs', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('medical.pdf', 500, 'application/pdf');
    $this->actingAs($this->user)->post(
        route('family.care-seekers.documents.store', $this->careSeeker),
        ['document_type' => 'medical_record', 'file' => $file]
    );
    $document = $this->careSeeker->documents()->first();

    $otherUser = User::factory()->create()->assignRole('family');
    Family::create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($otherUser)->get(
        route('family.care-seekers.documents.download', [$this->careSeeker, $document])
    );

    $response->assertStatus(403);
});

test('family can delete a care seeker document', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('medical.pdf', 500, 'application/pdf');
    $this->actingAs($this->user)->post(
        route('family.care-seekers.documents.store', $this->careSeeker),
        ['document_type' => 'medical_record', 'file' => $file]
    );
    $document = $this->careSeeker->documents()->first();

    $response = $this->actingAs($this->user)->delete(
        route('family.care-seekers.documents.destroy', [$this->careSeeker, $document])
    );

    $response->assertRedirect();
    expect($this->careSeeker->documents()->count())->toBe(0);
});

// ─── Care Seeker Photo ──────────────────────────────────────────────────────

test('family can upload a photo for a care seeker', function () {
    Storage::fake('public');
    $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $tmpPath = sys_get_temp_dir() . '/test_photo_' . uniqid() . '.png';
    file_put_contents($tmpPath, $pngBytes);
    $file = new \Illuminate\Http\UploadedFile($tmpPath, 'photo.png', 'image/png', null, true);

    $response = $this->actingAs($this->user)->post(
        route('family.care-seekers.photo.store', $this->careSeeker),
        ['photo' => $file]
    );

    @unlink($tmpPath);

    $response->assertRedirect();
    expect($this->careSeeker->fresh()->photo_path)->not->toBeNull();
});

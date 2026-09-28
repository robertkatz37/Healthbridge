<?php

use App\Models\AgencyCategory;
use App\Models\User;
use App\Services\Agency\AgencyFileUploadService;
use App\Services\Agency\AgencyProvisioningService;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();

    $this->owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, [
        'agency_category_id' => $category->id,
        'name' => 'Test Agency',
    ]);
});

test('admin can download an agency document during review', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('license.pdf', 500, 'application/pdf');
    app(AgencyFileUploadService::class)->storeDocument($this->agency, $file, 'license');
    $document = $this->agency->documents()->first();

    $response = $this->actingAs($this->admin)->get(
        route('admin.agencies.documents.download', [$this->agency, $document])
    );

    $response->assertOk();
});

test('family user cannot download an agency document via the admin route', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('license.pdf', 500, 'application/pdf');
    app(AgencyFileUploadService::class)->storeDocument($this->agency, $file, 'license');
    $document = $this->agency->documents()->first();

    $family = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($family)->get(
        route('admin.agencies.documents.download', [$this->agency, $document])
    );

    $response->assertStatus(403);
});

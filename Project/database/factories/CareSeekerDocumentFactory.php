<?php

namespace Database\Factories;

use App\Models\CareSeeker;
use App\Models\CareSeekerDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CareSeekerDocument> */
class CareSeekerDocumentFactory extends Factory
{
    protected $model = CareSeekerDocument::class;

    public function definition(): array
    {
        return [
            'care_seeker_id' => CareSeeker::factory(),
            'uploaded_by' => User::factory(),
            'document_type' => fake()->randomElement(['medical_record', 'insurance_card', 'poa_document', 'other']),
            'path' => 'care-seekers/test/documents/' . fake()->uuid() . '.pdf',
            'original_filename' => fake()->word() . '.pdf',
        ];
    }
}

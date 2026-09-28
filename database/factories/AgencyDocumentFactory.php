<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgencyDocument> */
class AgencyDocumentFactory extends Factory
{
    protected $model = AgencyDocument::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'document_type' => fake()->randomElement(['license', 'insurance', 'accreditation']),
            'path' => 'agencies/test/documents/' . fake()->uuid() . '.pdf',
            'original_filename' => fake()->word() . '.pdf',
            'expires_at' => fake()->dateTimeBetween('now', '+2 years'),
        ];
    }
}

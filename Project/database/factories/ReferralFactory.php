<?php

namespace Database\Factories;

use App\Enums\ReferralSource;
use App\Enums\ReferralStatus;
use App\Models\Agency;
use App\Models\Family;
use App\Models\Referral;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Referral> */
class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'family_id' => Family::factory(),
            'agency_id' => Agency::factory(),
            'status' => ReferralStatus::Pending->value,
            'source' => ReferralSource::MatchingEngine->value,
        ];
    }

    public function converted(): static
    {
        return $this->state(fn () => [
            'status' => ReferralStatus::Converted->value,
            'converted_at' => now(),
        ]);
    }
}

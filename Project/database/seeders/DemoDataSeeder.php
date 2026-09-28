<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\AgencyService;
use App\Models\Advisor;
use App\Models\BlogPost;
use App\Models\CareSeeker;
use App\Models\Commission;
use App\Models\Family;
use App\Models\Referral;
use App\Models\Review;
use App\Models\ReviewCategory;
use Illuminate\Database\Seeder;

/**
 * Dev/staging-only realistic demo dataset for manual QA and demos, per
 * TESTING.md. NEVER run in production (see DatabaseSeeder guard).
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Real city/state pairs, all present in
     * App\Services\Matching\CityCoordinateResolver's lookup table, so
     * seeded agencies resolve to exact coordinates rather than a
     * lower-precision state-centroid fallback. Clustered around a
     * handful of major metros (multiple agencies per city) rather than
     * one-per-random-state, so a family testing from any of these cities
     * finds several genuinely nearby options — matching how agencies
     * would realistically be distributed in a real regional market,
     * rather than one agency scattered into each of 50 states with nothing
     * near any given family. Without this, the (correct, working-as-
     * designed) Distance hard filter excludes almost every seeded
     * agency for any given test Care Seeker, since Faker's fake()->city()
     * generates fictional names scattered uniformly across all 50 states —
     * see DATABASE_DECISIONS.md.
     */
    private const DEMO_CITIES = [
        ['city' => 'Austin', 'state' => 'TX'], ['city' => 'Dallas', 'state' => 'TX'],
        ['city' => 'Houston', 'state' => 'TX'], ['city' => 'San Antonio', 'state' => 'TX'],
        ['city' => 'Denver', 'state' => 'CO'], ['city' => 'Phoenix', 'state' => 'AZ'],
        ['city' => 'Seattle', 'state' => 'WA'], ['city' => 'Portland', 'state' => 'OR'],
        ['city' => 'Chicago', 'state' => 'IL'], ['city' => 'Atlanta', 'state' => 'GA'],
        ['city' => 'Orlando', 'state' => 'FL'], ['city' => 'Tampa', 'state' => 'FL'],
        ['city' => 'Miami', 'state' => 'FL'], ['city' => 'Charlotte', 'state' => 'NC'],
        ['city' => 'Raleigh', 'state' => 'NC'], ['city' => 'Nashville', 'state' => 'TN'],
        ['city' => 'Columbus', 'state' => 'OH'], ['city' => 'Boston', 'state' => 'MA'],
    ];

    public function run(): void
    {
        $categories = AgencyCategory::all();

        // 20 agencies over 18 random picks could (and did, in testing)
        // leave some cities with only 1 agency or even 0 purely by
        // chance — a bad demo experience depending on which city
        // happens to get tested. Cycling through the city list
        // explicitly (2 full passes = 36 agencies, 2 per city
        // guaranteed) instead of random selection ensures every city
        // in the list has a genuinely useful number of nearby options
        // regardless of which one a family/tester picks.
        $cityAssignments = array_merge(self::DEMO_CITIES, self::DEMO_CITIES);
        $agencyIndex = 0;

        $agencies = Agency::factory()
            ->count(count($cityAssignments))
            ->state(function () use ($categories, $cityAssignments, &$agencyIndex) {
                $location = $cityAssignments[$agencyIndex % count($cityAssignments)];
                $agencyIndex++;
                return array_merge(['agency_category_id' => $categories->random()->id], $location);
            })
            ->create();

        $agencies->each(function (Agency $agency) {
            AgencyService::factory()->count(rand(2, 5))->create(['agency_id' => $agency->id]);

            Review::factory()->count(rand(0, 8))->create(['agency_id' => $agency->id]);
        });

        $families = Family::factory()->count(15)->create();

        $families->each(function (Family $family) use ($agencies) {
            $location = self::DEMO_CITIES[array_rand(self::DEMO_CITIES)];
            $seeker = CareSeeker::factory()->create([
                'family_id' => $family->id,
                'preferred_city' => $location['city'],
                'preferred_state' => $location['state'],
            ]);

            $referral = Referral::factory()->create([
                'family_id' => $family->id,
                'care_seeker_id' => $seeker->id,
                'agency_id' => $agencies->random()->id,
            ]);

            if (rand(0, 1)) {
                $referral->update(['status' => 'converted', 'converted_at' => now()]);
                Commission::factory()->create(['referral_id' => $referral->id]);
            }
        });

        Advisor::factory()->count(5)->create();

        BlogPost::factory()->count(8)->create();
    }
}

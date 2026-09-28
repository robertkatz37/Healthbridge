<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the US (structure for future countries), all 50 states + DC, and a
 * starter set of major metro-hub cities. Full city-level data import is a
 * Phase 14 (CMS/SEO) content task per PROJECT_ROADMAP.md — this seeder
 * establishes the correct structure now so later phases only need to import
 * rows, not design schema.
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $us = Country::updateOrCreate(['code' => 'US'], ['name' => 'United States', 'is_active' => true]);

        $states = [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
            'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
            'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
            'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
            'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
            'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
            'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
            'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
            'DC' => 'District of Columbia',
        ];

        $stateModels = [];
        foreach ($states as $code => $name) {
            $stateModels[$code] = State::updateOrCreate(
                ['country_id' => $us->id, 'code' => $code],
                ['name' => $name, 'is_active' => true]
            );
        }

        // Per SRS §3.10 / the 60-largest-metro-area homepage linking requirement —
        // starter set of major metro hubs; full 60-city list is a content task.
        $metroHubs = [
            ['name' => 'New York', 'state' => 'NY'], ['name' => 'Los Angeles', 'state' => 'CA'],
            ['name' => 'Chicago', 'state' => 'IL'], ['name' => 'Houston', 'state' => 'TX'],
            ['name' => 'Dallas', 'state' => 'TX'], ['name' => 'Philadelphia', 'state' => 'PA'],
            ['name' => 'Miami', 'state' => 'FL'], ['name' => 'Atlanta', 'state' => 'GA'],
            ['name' => 'Boston', 'state' => 'MA'], ['name' => 'San Jose', 'state' => 'CA'],
            ['name' => 'Phoenix', 'state' => 'AZ'], ['name' => 'Seattle', 'state' => 'WA'],
            ['name' => 'Minneapolis', 'state' => 'MN'], ['name' => 'San Diego', 'state' => 'CA'],
            ['name' => 'Denver', 'state' => 'CO'],
        ];

        foreach ($metroHubs as $hub) {
            $state = $stateModels[$hub['state']];

            City::updateOrCreate(
                ['state_id' => $state->id, 'name' => $hub['name']],
                ['slug' => Str::slug($hub['name'].'-'.$hub['state']), 'is_metro_hub' => true, 'is_active' => true]
            );
        }
    }
}

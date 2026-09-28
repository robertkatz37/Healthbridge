<?php

namespace Database\Seeders;

use App\Models\CommissionRule;
use Illuminate\Database\Seeder;

class CommissionRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Platform default rule (agency_id null) per SRS §15 — agency-specific
        // overrides can be added later without touching this default.
        CommissionRule::firstOrCreate(
            ['agency_id' => null, 'rule_type' => 'flat'],
            ['amount' => 500.00, 'is_active' => true]
        );
    }
}

<?php

namespace App\Services\Family;

use App\Models\Family;

class FamilyDashboardService
{
    public function summary(Family $family): array
    {
        return [
            'care_seeker_count' => $family->careSeekers()->count(),
            'favorite_count' => $family->favorites()->count(),
            'completed_assessments' => \App\Models\NeedsAssessment::whereIn(
                'care_seeker_id',
                $family->careSeekers()->pluck('id')
            )->where('status', 'completed')->count(),
            'draft_assessments' => \App\Models\NeedsAssessment::whereIn(
                'care_seeker_id',
                $family->careSeekers()->pluck('id')
            )->where('status', 'in_progress')->count(),
        ];
    }
}

<?php

namespace App\Services\Review;

use App\Models\Review;
use App\Models\User;
use App\Notifications\Review\ReviewReported;
use Illuminate\Validation\ValidationException;

class ReviewReportService
{
    public function report(Review $review, User $reporter, string $reason, ?string $details = null): \App\Models\ReviewReport
    {
        if ($review->reports()->where('reporter_id', $reporter->id)->exists()) {
            throw ValidationException::withMessages(['reason' => 'You have already reported this review.']);
        }

        $report = $review->reports()->create([
            'reporter_id' => $reporter->id,
            'reason' => $reason,
            'details' => $details,
        ]);

        activity()->causedBy($reporter)->performedOn($review)->log('Review reported: ' . $reason);

        \App\Models\User::role('super_admin')->get()->each(fn (User $admin) => $admin->notify(new ReviewReported($report)));

        return $report;
    }

    public function resolve(\App\Models\ReviewReport $report, string $status, User $actor): void
    {
        $report->update(['status' => $status, 'resolved_by' => $actor->id, 'resolved_at' => now()]);

        activity()->causedBy($actor)->performedOn($report)->log('Review report marked ' . $status);
    }
}

<?php

namespace App\Services\Review;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;
use App\Notifications\Review\ReviewApproved;
use App\Notifications\Review\ReviewRejected;

/**
 * Owns every Super Admin moderation action (Approve, Reject, Hide,
 * Restore, Feature, Delete) — logged via the shared activity() helper
 * rather than a new dedicated history table. Approving or un-publishing
 * a review always goes through recalculateAgencyScore() so
 * agencies.review_score never drifts out of sync with what's actually
 * published.
 */
class ReviewModerationService
{
    public function approve(Review $review, ?User $actor = null): void
    {
        $review->update([
            'status' => ReviewStatus::Published->value,
            'published_at' => $review->published_at ?? now(),
            'verified_by' => $actor?->id,
        ]);

        activity()->causedBy($actor)->performedOn($review)->log('Review approved and published');
        $this->recalculateAgencyScore($review);

        $review->family->user->notify(new ReviewApproved($review));
    }

    public function reject(Review $review, string $reason, ?User $actor = null): void
    {
        $review->update(['status' => ReviewStatus::Rejected->value]);

        activity()->causedBy($actor)->performedOn($review)->withProperties(['reason' => $reason])->log('Review rejected');
        $this->recalculateAgencyScore($review);

        $review->family->user->notify(new ReviewRejected($review, $reason));
    }

    public function hide(Review $review, string $reason, ?User $actor = null): void
    {
        $review->update(['status' => ReviewStatus::Hidden->value]);

        activity()->causedBy($actor)->performedOn($review)->withProperties(['reason' => $reason])->log('Review hidden');
        $this->recalculateAgencyScore($review);
    }

    public function restore(Review $review, ?User $actor = null): void
    {
        $review->update(['status' => ReviewStatus::Published->value]);

        activity()->causedBy($actor)->performedOn($review)->log('Review restored to published');
        $this->recalculateAgencyScore($review);
    }

    public function feature(Review $review, ?User $actor = null): void
    {
        $review->update(['is_featured' => true]);
        activity()->causedBy($actor)->performedOn($review)->log('Review marked as featured');
    }

    public function unfeature(Review $review, ?User $actor = null): void
    {
        $review->update(['is_featured' => false]);
        activity()->causedBy($actor)->performedOn($review)->log('Review removed from featured');
    }

    public function delete(Review $review, ?User $actor = null): void
    {
        $agency = $review->agency;
        activity()->causedBy($actor)->performedOn($review)->log('Review deleted');
        $review->delete();
        $this->recalculateAgencyScoreFor($agency);
    }

    public function recalculateAgencyScore(Review $review): void
    {
        $this->recalculateAgencyScoreFor($review->agency);
    }

    public function recalculateAgencyScoreFor(\App\Models\Agency $agency): void
    {
        $average = $agency->reviews()->published()->avg('overall_rating');
        $agency->update(['review_score' => $average ? round($average, 2) : null]);
    }
}

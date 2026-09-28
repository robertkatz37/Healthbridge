<?php

namespace App\Services\Review;

use App\Models\Review;
use App\Models\User;

class ReviewVoteService
{
    public function vote(Review $review, User $user, bool $isHelpful): void
    {
        $review->votes()->updateOrCreate(
            ['user_id' => $user->id],
            ['is_helpful' => $isHelpful],
        );

        $this->recalculateCounts($review);
    }

    public function removeVote(Review $review, User $user): void
    {
        $review->votes()->where('user_id', $user->id)->delete();
        $this->recalculateCounts($review);
    }

    private function recalculateCounts(Review $review): void
    {
        $review->update([
            'helpful_count' => $review->votes()->where('is_helpful', true)->count(),
            'not_helpful_count' => $review->votes()->where('is_helpful', false)->count(),
        ]);
    }
}

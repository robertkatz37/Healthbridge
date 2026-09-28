<?php

namespace App\Services\Review;

use App\Enums\ReferralStatus;
use App\Enums\ReviewStatus;
use App\Models\Family;
use App\Models\Referral;
use App\Models\Review;
use App\Models\ReviewCategory;
use Illuminate\Validation\ValidationException;

/**
 * Owns eligibility and creation for a new Review — "Only verified
 * families who completed a successful Move-In through a Referral may
 * submit reviews." A review is tied to exactly one Referral (enforced
 * by a DB-level unique constraint on reviews.referral_id, not just an
 * application-level check).
 */
class ReviewSubmissionService
{
    public function eligibleStatuses(): array
    {
        return [ReferralStatus::MoveInConfirmed, ReferralStatus::Converted];
    }

    public function isEligible(Referral $referral): bool
    {
        return in_array($referral->status, $this->eligibleStatuses(), true)
            && !$referral->review()->exists();
    }

    public function assertEligible(Family $family, Referral $referral): void
    {
        if ($referral->family_id !== $family->id) {
            throw ValidationException::withMessages(['referral_id' => 'This referral does not belong to your family.']);
        }

        if (!in_array($referral->status, $this->eligibleStatuses(), true)) {
            throw ValidationException::withMessages(['referral_id' => 'A review can only be submitted after a confirmed move-in.']);
        }

        if ($referral->review()->exists()) {
            throw ValidationException::withMessages(['referral_id' => 'A review has already been submitted for this referral.']);
        }
    }

    /**
     * @param array<string, int> $categoryRatings review_category.code => 1-5 rating
     * @param array<int, array{type: string, path: string, original_filename: ?string, mime_type: ?string, size_bytes: ?int}> $media
     */
    public function submit(
        Family $family,
        Referral $referral,
        string $title,
        string $body,
        array $categoryRatings,
        ?bool $wouldRecommend,
        bool $isAnonymous,
        array $media = [],
    ): Review {
        $this->assertEligible($family, $referral);

        if (empty($categoryRatings)) {
            throw ValidationException::withMessages(['category_ratings' => 'At least one category rating is required.']);
        }

        $overallRating = round(array_sum($categoryRatings) / count($categoryRatings), 2);

        $review = Review::create([
            'agency_id' => $referral->agency_id,
            'family_id' => $family->id,
            'referral_id' => $referral->id,
            'reviewer_name' => $family->user->name,
            // reviewer_relationship is a constrained enum describing the
            // reviewer's CATEGORY (resident / family_member /
            // former_resident) — not the specific familial relationship
            // (daughter, son, spouse) stored on Family::relationship_to_seeker,
            // which is free text and would never match this enum's
            // values. This service is specifically the family-portal
            // submission path, so the category is always family_member;
            // a resident or former-resident self-review would go through
            // a different path with its own value. (Previously this used
            // $family->relationship_to_seeker directly, which silently
            // passed SQLite's lax enum handling in every test but throws
            // a real MySQL "Data truncated" error for any value outside
            // the 3 allowed ones — including its own fallback string,
            // "Family Member", which didn't match the enum's lowercase
            // "family_member" either.)
            'reviewer_relationship' => 'family_member',
            'is_anonymous' => $isAnonymous,
            'title' => $title,
            'body' => $body,
            'overall_rating' => $overallRating,
            'would_recommend' => $wouldRecommend,
            'is_verified' => true,
            'verified_by' => null,
            'status' => ReviewStatus::PendingModeration->value,
        ]);

        foreach ($categoryRatings as $code => $rating) {
            $category = ReviewCategory::where('code', $code)->first();
            if ($category) {
                $review->categoryRatings()->create(['review_category_id' => $category->id, 'rating' => $rating]);
            }
        }

        foreach ($media as $item) {
            $review->media()->create($item);
        }

        activity()->causedBy($family->user)->performedOn($review)->log('Review submitted, pending moderation');

        return $review;
    }
}

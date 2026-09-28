<?php

namespace App\Http\Controllers;

use App\Http\Requests\VoteReviewRequest;
use App\Models\Review;
use App\Services\Review\ReviewVoteService;
use Illuminate\Http\JsonResponse;

class ReviewVoteController extends Controller
{
    public function __construct(
        private readonly ReviewVoteService $votes,
    ) {}

    public function store(VoteReviewRequest $request, Review $review): JsonResponse
    {
        $this->votes->vote($review, $request->user(), $request->boolean('is_helpful'));

        return response()->json([
            'helpful_count' => $review->fresh()->helpful_count,
            'not_helpful_count' => $review->fresh()->not_helpful_count,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\ReplyToReviewRequest;
use App\Models\Review;
use App\Notifications\Review\AgencyResponseAdded;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $request->user()->agency;
        $this->authorize('view', $agency);

        $query = $agency->reviews()->with(['family.user', 'categoryRatings.category', 'reply'])->latest();

        $filter = $request->input('filter', 'all');
        match ($filter) {
            'published' => $query->where('status', 'published'),
            'pending_response' => $query->where('status', 'published')->whereDoesntHave('reply'),
            default => null,
        };

        $reviews = $query->paginate(15)->withQueryString();

        $ratingBreakdown = $agency->reviews()->published()
            ->selectRaw('round(overall_rating) as rounded_rating, count(*) as count')
            ->groupBy('rounded_rating')->orderByDesc('rounded_rating')->pluck('count', 'rounded_rating');

        return view('agency.reviews.index', compact('reviews', 'filter', 'ratingBreakdown'));
    }

    public function reply(ReplyToReviewRequest $request, Review $review): RedirectResponse
    {
        $reply = $review->reply;

        if ($reply) {
            $reply->updateBodyWithHistory($request->body);
            activity()->causedBy($request->user())->performedOn($review)->log('Agency response edited');
        } else {
            $reply = $review->reply()->create(['user_id' => $request->user()->id, 'body' => $request->body]);
            activity()->causedBy($request->user())->performedOn($review)->log('Agency response added');
        }

        $review->family->user->notify(new AgencyResponseAdded($review));

        return back()->with('status', 'reply-saved');
    }
}

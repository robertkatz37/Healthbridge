<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\SubmitReviewRequest;
use App\Models\Referral;
use App\Models\Review;
use App\Notifications\Review\ReviewSubmitted;
use App\Services\Review\ReviewSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewSubmissionService $submission,
    ) {}

    public function index(Request $request): View
    {
        $family = $request->user()->family;

        $myReviews = $family->reviews()->with(['agency', 'reply'])->latest()->get();

        $pendingReferrals = Referral::where('family_id', $family->id)
            ->whereIn('status', array_map(fn ($s) => $s->value, $this->submission->eligibleStatuses()))
            ->whereDoesntHave('review')
            ->with('agency')
            ->get();

        return view('family.reviews.index', compact('myReviews', 'pendingReferrals'));
    }

    public function create(Request $request, Referral $referral): View
    {
        $this->authorize('create', Review::class);

        $this->submission->assertEligible($request->user()->family, $referral);

        $categories = \App\Models\ReviewCategory::active()->get();

        return view('family.reviews.create', compact('referral', 'categories'));
    }

    public function store(SubmitReviewRequest $request, Referral $referral): RedirectResponse
    {
        $family = $request->user()->family;

        $media = [];
        foreach ($request->file('media', []) as $file) {
            $path = $file->store('review-media', 'public');
            $media[] = [
                'type' => str_starts_with($file->getMimeType(), 'video') ? 'video' : 'photo',
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ];
        }

        $review = $this->submission->submit(
            $family,
            $referral,
            $request->title,
            $request->body,
            $request->category_ratings,
            $request->has('would_recommend') ? $request->boolean('would_recommend') : null,
            $request->boolean('is_anonymous'),
            $media,
        );

        \App\Models\User::role('super_admin')->get()->each(fn ($admin) => $admin->notify(new ReviewSubmitted($review)));

        return redirect()->route('family.reviews.index')->with('status', 'review-submitted');
    }
}

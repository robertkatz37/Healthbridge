<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectReviewRequest;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Services\Review\ReviewModerationService;
use App\Services\Review\ReviewReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gated by ReviewPolicy::moderate() throughout — 'reviews.moderate',
 * held by both 'moderator' and 'platform_admin', not just 'super_admin'.
 */
class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewModerationService $moderation,
        private readonly ReviewReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Review::class);
        abort_unless($request->user()->can('reviews.moderate'), 403);

        $filter = $request->input('filter', 'pending');

        if ($filter === 'reported') {
            $reportedReviews = ReviewReport::where('status', 'pending')
                ->with(['review.agency', 'review.family.user', 'reporter'])
                ->latest()->paginate(20)->withQueryString();

            return view('admin.reviews.index', ['filter' => $filter, 'reportedReviews' => $reportedReviews, 'reviews' => null, 'counts' => $this->counts()]);
        }

        $query = Review::withTrashed()->with(['agency', 'family.user'])->latest();
        match ($filter) {
            'pending' => $query->where('status', 'pending_moderation'),
            'published' => $query->where('status', 'published'),
            'rejected' => $query->where('status', 'rejected'),
            'hidden' => $query->where('status', 'hidden'),
            'featured' => $query->where('is_featured', true),
            default => null,
        };

        $reviews = $query->paginate(20)->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'filter') + ['reportedReviews' => null, 'counts' => $this->counts()]);
    }

    private function counts(): array
    {
        return [
            'pending' => Review::where('status', 'pending_moderation')->count(),
            'reported' => ReviewReport::where('status', 'pending')->count(),
        ];
    }

    public function show(Request $request, Review $review): View
    {
        $this->authorize('moderate', $review);

        $review->load(['agency', 'family.user', 'categoryRatings.category', 'media', 'reply', 'reports.reporter']);

        return view('admin.reviews.show', compact('review'));
    }

    public function approve(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);
        $this->moderation->approve($review, $request->user());

        return back()->with('status', 'review-approved');
    }

    public function reject(RejectReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);
        $this->moderation->reject($review, $request->reason, $request->user());

        return back()->with('status', 'review-rejected');
    }

    public function hide(RejectReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);
        $this->moderation->hide($review, $request->reason, $request->user());

        return back()->with('status', 'review-hidden');
    }

    public function restore(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);
        $this->moderation->restore($review, $request->user());

        return back()->with('status', 'review-restored');
    }

    public function feature(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);
        $this->moderation->feature($review, $request->user());

        return back()->with('status', 'review-featured');
    }

    public function unfeature(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);
        $this->moderation->unfeature($review, $request->user());

        return back()->with('status', 'review-unfeatured');
    }

    public function destroy(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);
        $this->moderation->delete($review, $request->user());

        return back()->with('status', 'review-deleted');
    }

    public function resolveReport(Request $request, ReviewReport $reviewReport): RedirectResponse
    {
        $this->authorize('moderate', $reviewReport->review);
        $request->validate(['status' => ['required', 'in:reviewed,dismissed']]);

        $this->reports->resolve($reviewReport, $request->status, $request->user());

        return back()->with('status', 'report-resolved');
    }
}

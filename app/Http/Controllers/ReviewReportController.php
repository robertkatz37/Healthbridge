<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportReviewRequest;
use App\Models\Review;
use App\Services\Review\ReviewReportService;
use Illuminate\Http\RedirectResponse;

class ReviewReportController extends Controller
{
    public function __construct(
        private readonly ReviewReportService $reports,
    ) {}

    public function store(ReportReviewRequest $request, Review $review): RedirectResponse
    {
        $this->reports->report($review, $request->user(), $request->reason, $request->details);

        return back()->with('status', 'review-reported');
    }
}

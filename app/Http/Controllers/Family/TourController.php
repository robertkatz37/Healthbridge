<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\CancelTourRequestRequest;
use App\Models\Family;
use App\Models\TourRequest;
use App\Services\Referral\TourManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Family-facing tours — view + cancel only ("Cancel tour requests" is
 * explicitly the family's one allowed action; scheduling/rescheduling
 * happens through the Advisor or Agency).
 */
class TourController extends Controller
{
    public function __construct(
        private readonly TourManagementService $tours,
    ) {}

    public function index(Request $request): View
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();

        $tours = TourRequest::where('family_id', $family->id)
            ->with(['agency', 'careSeeker', 'referral'])
            ->orderBy('requested_date')
            ->paginate(20);

        return view('family.tours.index', compact('tours'));
    }

    public function cancel(CancelTourRequestRequest $request, TourRequest $tourRequest): RedirectResponse
    {
        $this->tours->cancel($tourRequest, $request->user());

        return back()->with('status', 'tour-cancelled');
    }
}

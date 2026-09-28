<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\Advisor;
use App\Services\Advisor\LeadTimelineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        private readonly LeadTimelineService $timeline,
    ) {}

    public function index(Request $request): View
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('view', $advisor);

        $entries = $this->timeline->forAdvisor($advisor);

        return view('advisor.activity', compact('entries'));
    }
}

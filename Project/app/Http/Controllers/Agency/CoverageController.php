<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StoreCoverageRequest;
use App\Models\AgencyCoverage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoverageController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $coverage = $agency->coverage;

        return view('agency.coverage', compact('agency', 'coverage'));
    }

    public function store(StoreCoverageRequest $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();

        $agency->coverage()->create($request->validated());

        activity()->causedBy($request->user())->performedOn($agency)->log('Coverage area added');

        return back()->with('status', 'coverage-added');
    }

    public function destroy(Request $request, AgencyCoverage $coverage): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);
        abort_unless($coverage->agency_id === $agency->id, 403);

        $coverage->delete();

        return back()->with('status', 'coverage-deleted');
    }
}

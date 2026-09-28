<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advisor\StoreAdvisorTerritoryRequest;
use App\Models\Advisor;
use App\Models\AdvisorTerritory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TerritoryController extends Controller
{
    public function index(Request $request): View
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('view', $advisor);

        $territories = $advisor->territories;

        return view('advisor.territories', compact('advisor', 'territories'));
    }

    public function store(StoreAdvisorTerritoryRequest $request): RedirectResponse
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('update', $advisor);

        $advisor->territories()->create($request->validated());

        activity()->causedBy($request->user())->performedOn($advisor)->log('Territory added');

        return back()->with('status', 'territory-added');
    }

    public function destroy(Request $request, AdvisorTerritory $territory): RedirectResponse
    {
        $this->authorize('delete', $territory);

        $territory->delete();

        return back()->with('status', 'territory-deleted');
    }
}

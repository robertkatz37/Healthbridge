<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StoreServiceRequest;
use App\Models\AgencyService;
use App\Models\ServiceCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $services = $agency->services()->with('catalogService')->get();
        $catalog = ServiceCatalog::active()->get();

        return view('agency.services', compact('agency', 'services', 'catalog'));
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();

        $agency->services()->create($request->validated());

        activity()->causedBy($request->user())->performedOn($agency)->log('Service added');

        return back()->with('status', 'service-added');
    }

    public function update(StoreServiceRequest $request, AgencyService $service): RedirectResponse
    {
        $this->authorize('update', $request->user()->currentAgency());
        abort_unless($service->agency_id === $request->user()->currentAgency()->id, 403);

        $service->update($request->validated());

        return back()->with('status', 'service-updated');
    }

    public function destroy(Request $request, AgencyService $service): RedirectResponse
    {
        $this->authorize('update', $request->user()->currentAgency());
        abort_unless($service->agency_id === $request->user()->currentAgency()->id, 403);

        $service->delete();

        return back()->with('status', 'service-deleted');
    }
}

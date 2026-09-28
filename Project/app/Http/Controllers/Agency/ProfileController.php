<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\UpdateProfileRequest;
use App\Models\AgencyCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $categories = AgencyCategory::active()->get();

        return view('agency.profile', compact('agency', 'categories'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);

        $agency->update($request->validated());

        activity()->causedBy($request->user())->performedOn($agency)->log('Agency profile updated');

        return back()->with('status', 'profile-updated');
    }
}

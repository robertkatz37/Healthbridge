<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\UpdateFamilyProfileRequest;
use App\Models\Family;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Family-specific profile fields (phone, relationship to seeker) only.
 * Name/email/password/avatar are managed by the existing Auth\ProfileController
 * (Phase 5) — deliberately not duplicated here; this view links to it.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('view', $family);

        return view('family.profile', compact('family'));
    }

    public function update(UpdateFamilyProfileRequest $request): RedirectResponse
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();

        $family->update($request->validated());

        activity()->causedBy($request->user())->performedOn($family)->log('Family profile updated');

        return back()->with('status', 'profile-updated');
    }
}

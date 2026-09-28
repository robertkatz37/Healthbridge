<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advisor\StoreAdvisorPhotoRequest;
use App\Http\Requests\Advisor\UpdateAdvisorProfileRequest;
use App\Models\Advisor;
use App\Services\Advisor\AdvisorFileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AdvisorFileUploadService $uploads,
    ) {}

    public function edit(Request $request): View
    {
        $advisor = Advisor::firstOrCreate(['user_id' => $request->user()->id], ['is_active' => true]);
        $this->authorize('view', $advisor);

        return view('advisor.profile', compact('advisor'));
    }

    public function update(UpdateAdvisorProfileRequest $request): RedirectResponse
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();

        $data = $request->validated();
        $data['is_available'] = $request->boolean('is_available');

        $advisor->update($data);

        activity()->causedBy($request->user())->performedOn($advisor)->log('Advisor profile updated');

        return back()->with('status', 'profile-updated');
    }

    public function uploadPhoto(StoreAdvisorPhotoRequest $request): RedirectResponse
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();

        $this->uploads->storePhoto($advisor, $request->file('photo'));

        activity()->causedBy($request->user())->performedOn($advisor)->log('Advisor photo updated');

        return back()->with('status', 'photo-updated');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('update', $advisor);

        $this->uploads->deletePhoto($advisor);

        return back()->with('status', 'photo-removed');
    }
}

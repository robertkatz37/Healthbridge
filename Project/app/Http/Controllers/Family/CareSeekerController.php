<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreCareSeekerPhotoRequest;
use App\Http\Requests\Family\StoreCareSeekerRequest;
use App\Http\Requests\Family\UpdateCareSeekerRequest;
use App\Models\CareSeeker;
use App\Models\Family;
use App\Services\Family\CareSeekerFileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareSeekerController extends Controller
{
    public function __construct(
        private readonly CareSeekerFileUploadService $uploads,
    ) {}

    public function index(Request $request): View
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('viewAny', CareSeeker::class);

        $careSeekers = $family->careSeekers()->withCount(['needsAssessments as completed_assessments_count' => function ($query) {
            $query->where('status', 'completed');
        }])->get();

        return view('family.care-seekers.index', compact('careSeekers'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', CareSeeker::class);

        return view('family.care-seekers.create');
    }

    public function store(StoreCareSeekerRequest $request): RedirectResponse
    {
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();

        $careSeeker = $family->careSeekers()->create($request->validated());

        activity()->causedBy($request->user())->performedOn($careSeeker)->log('Care seeker profile created');

        return redirect()->route('family.care-seekers.edit', $careSeeker)
            ->with('status', 'care-seeker-created');
    }

    public function edit(Request $request, CareSeeker $careSeeker): View
    {
        $this->authorize('view', $careSeeker);

        return view('family.care-seekers.edit', compact('careSeeker'));
    }

    public function update(UpdateCareSeekerRequest $request, CareSeeker $careSeeker): RedirectResponse
    {
        $careSeeker->update($request->validated());

        activity()->causedBy($request->user())->performedOn($careSeeker)->log('Care seeker profile updated');

        return back()->with('status', 'care-seeker-updated');
    }

    public function destroy(Request $request, CareSeeker $careSeeker): RedirectResponse
    {
        $this->authorize('delete', $careSeeker);

        activity()->causedBy($request->user())
            ->withProperties(['care_seeker_name' => $careSeeker->full_name])
            ->log('Care seeker profile deleted');

        $careSeeker->delete();

        return redirect()->route('family.care-seekers.index')
            ->with('status', 'care-seeker-deleted');
    }

    public function uploadPhoto(StoreCareSeekerPhotoRequest $request, CareSeeker $careSeeker): RedirectResponse
    {
        $this->uploads->storePhoto($careSeeker, $request->file('photo'));

        activity()->causedBy($request->user())->performedOn($careSeeker)->log('Care seeker photo updated');

        return back()->with('status', 'photo-updated');
    }

    public function destroyPhoto(Request $request, CareSeeker $careSeeker): RedirectResponse
    {
        $this->authorize('update', $careSeeker);

        $this->uploads->deletePhoto($careSeeker);

        return back()->with('status', 'photo-removed');
    }
}

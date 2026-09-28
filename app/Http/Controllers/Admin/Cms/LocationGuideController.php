<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\AgencyCategory;
use App\Models\City;
use App\Models\CityGuide;
use App\Models\ServiceGuide;
use App\Models\State;
use App\Models\StateGuide;
use App\Services\Cms\SeoMetaService;
use App\Services\Cms\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationGuideController extends Controller
{
    public function __construct(
        private readonly SlugService $slugs,
        private readonly SeoMetaService $seo,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('cms.manage'), 403);

        $states = State::active()->with('guide')->orderBy('name')->get();
        $cities = City::active()->with(['state', 'guide'])->orderBy('name')->get();
        $services = AgencyCategory::active()->with('guide')->get();

        return view('admin.cms.locations.index', compact('states', 'cities', 'services'));
    }

    public function editState(Request $request, State $state): View
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $guide = $state->guide ?? new StateGuide(['state_id' => $state->id]);

        return view('admin.cms.locations.edit-state', compact('state', 'guide'));
    }

    public function updateState(Request $request, State $state): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $request->validate(['intro_content' => ['nullable', 'string'], 'status' => ['required', 'in:draft,published']]);

        $guide = StateGuide::updateOrCreate(['state_id' => $state->id], [
            'slug' => $state->guide?->slug ?? $this->slugs->unique(StateGuide::class, $state->name),
            'intro_content' => $request->intro_content,
            'status' => $request->status,
        ]);
        $this->seo->upsertFor($guide, $request->only(['meta_title', 'meta_description']));

        activity()->causedBy($request->user())->performedOn($guide)->log('State guide updated: ' . $state->name);

        return back()->with('status', 'guide-updated');
    }

    public function editCity(Request $request, City $city): View
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $guide = $city->guide ?? new CityGuide(['city_id' => $city->id]);

        return view('admin.cms.locations.edit-city', compact('city', 'guide'));
    }

    public function updateCity(Request $request, City $city): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $request->validate(['intro_content' => ['nullable', 'string'], 'status' => ['required', 'in:draft,published']]);

        $guide = CityGuide::updateOrCreate(['city_id' => $city->id], [
            'slug' => $city->guide?->slug ?? $this->slugs->unique(CityGuide::class, $city->name),
            'intro_content' => $request->intro_content,
            'status' => $request->status,
        ]);
        $this->seo->upsertFor($guide, $request->only(['meta_title', 'meta_description']));

        activity()->causedBy($request->user())->performedOn($guide)->log('City guide updated: ' . $city->name);

        return back()->with('status', 'guide-updated');
    }

    public function editService(Request $request, AgencyCategory $service): View
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $guide = $service->guide ?? new ServiceGuide(['agency_category_id' => $service->id]);

        return view('admin.cms.locations.edit-service', compact('service', 'guide'));
    }

    public function updateService(Request $request, AgencyCategory $service): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $request->validate(['intro_content' => ['nullable', 'string'], 'status' => ['required', 'in:draft,published']]);

        $guide = ServiceGuide::updateOrCreate(['agency_category_id' => $service->id], [
            'slug' => $service->guide?->slug ?? $this->slugs->unique(ServiceGuide::class, $service->name),
            'intro_content' => $request->intro_content,
            'status' => $request->status,
        ]);
        $this->seo->upsertFor($guide, $request->only(['meta_title', 'meta_description']));

        activity()->causedBy($request->user())->performedOn($guide)->log('Service guide updated: ' . $service->name);

        return back()->with('status', 'guide-updated');
    }
}

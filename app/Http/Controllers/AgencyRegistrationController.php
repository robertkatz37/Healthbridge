<?php

namespace App\Http\Controllers;

use App\Http\Requests\Agency\StoreBasicInfoRequest;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\AgencyCoverage;
use App\Models\AgencyHour;
use App\Models\AgencyService;
use App\Models\ServiceCatalog;
use App\Services\Agency\AgencyOnboardingService;
use App\Services\Agency\AgencyProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 5-step Agency onboarding wizard. Each step persists directly to the
 * database on submission (see AgencyOnboardingService docblock) so
 * progress is never lost — a returning owner resumes exactly where they
 * left off via Agency::onboarding_step.
 */
class AgencyRegistrationController extends Controller
{
    public function __construct(
        private readonly AgencyProvisioningService $provisioning,
        private readonly AgencyOnboardingService $onboarding,
    ) {}

    /**
     * Entry point — resumes an in-progress agency or starts fresh at Step 1.
     */
    public function start(Request $request): RedirectResponse
    {
        $agency = $this->ownedAgency($request->user());

        if ($agency && !$agency->isOnboardingComplete()) {
            $step = $this->onboarding->resumeStep($agency);
            return redirect()->route("agency.register.step{$step}");
        }

        if ($agency && $agency->isOnboardingComplete()) {
            return redirect()->route('agency.dashboard');
        }

        return redirect()->route('agency.register.step1');
    }

    // ─── Step 1: Basic Info ───────────────────────────────────────────────────

    public function step1(Request $request): View
    {
        $agency = $this->ownedAgency($request->user());
        $categories = AgencyCategory::active()->get();

        return view('agency-registration.step1', compact('agency', 'categories'));
    }

    public function storeStep1(StoreBasicInfoRequest $request): RedirectResponse
    {
        $agency = $this->ownedAgency($request->user());

        if ($agency) {
            $agency->update($request->validated());
        } else {
            $agency = $this->provisioning->createDraftAgency($request->user(), $request->validated());
        }

        $this->onboarding->advanceStep($agency, 1);

        return redirect()->route('agency.register.step2')
            ->with('status', 'step-saved');
    }

    // ─── Step 2: Services ─────────────────────────────────────────────────────

    public function step2(Request $request): View|RedirectResponse
    {
        $agency = $this->requireDraftAgency($request);
        if ($agency instanceof RedirectResponse) return $agency;

        $catalog = ServiceCatalog::active()
            ->when($agency->agency_category_id, fn ($q) => $q->where('agency_category_id', $agency->agency_category_id))
            ->get();
        $services = $agency->services;

        return view('agency-registration.step2', compact('agency', 'catalog', 'services'));
    }

    public function storeStep2(Request $request): RedirectResponse
    {
        $agency = $this->ownedAgency($request->user());
        if (!$agency) return redirect()->route('agency.register.step1');

        $request->validate([
            'services' => ['required', 'array', 'min:1'],
            'services.*.name' => ['required', 'string', 'max:255'],
            'services.*.service_catalog_id' => ['nullable', 'exists:services_catalog,id'],
            'services.*.price_from' => ['nullable', 'numeric', 'min:0'],
        ]);

        $agency->services()->delete();
        foreach ($request->input('services') as $service) {
            AgencyService::create([
                'agency_id' => $agency->id,
                'service_catalog_id' => $service['service_catalog_id'] ?? null,
                'name' => $service['name'],
                'price_from' => $service['price_from'] ?? null,
                'is_active' => true,
            ]);
        }

        $this->onboarding->advanceStep($agency, 2);

        return redirect()->route('agency.register.step3')->with('status', 'step-saved');
    }

    // ─── Step 3: Coverage + Hours ─────────────────────────────────────────────

    public function step3(Request $request): View|RedirectResponse
    {
        $agency = $this->requireDraftAgency($request);
        if ($agency instanceof RedirectResponse) return $agency;

        $coverage = $agency->coverage;
        $hours = $agency->hours()->orderBy('day_of_week')->get();

        return view('agency-registration.step3', compact('agency', 'coverage', 'hours'));
    }

    public function storeStep3(Request $request): RedirectResponse
    {
        $agency = $this->ownedAgency($request->user());
        if (!$agency) return redirect()->route('agency.register.step1');

        $request->validate([
            'coverage' => ['required', 'array', 'min:1'],
            'coverage.*.city' => ['required', 'string', 'max:100'],
            'coverage.*.state' => ['required', 'string', 'max:100'],
            'coverage.*.radius_miles' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $agency->coverage()->delete();
        foreach ($request->input('coverage') as $area) {
            AgencyCoverage::create([
                'agency_id' => $agency->id,
                'city' => $area['city'],
                'state' => $area['state'],
                'radius_miles' => $area['radius_miles'] ?? null,
            ]);
        }

        if ($request->has('days')) {
            $agency->hours()->delete();
            foreach ($request->input('days') as $dayOfWeek => $day) {
                AgencyHour::create([
                    'agency_id' => $agency->id,
                    'day_of_week' => $dayOfWeek,
                    'is_closed' => isset($day['is_closed']),
                    'open_time' => $day['open_time'] ?? null,
                    'close_time' => $day['close_time'] ?? null,
                ]);
            }
        }

        $this->onboarding->advanceStep($agency, 3);

        return redirect()->route('agency.register.step4')->with('status', 'step-saved');
    }

    // ─── Step 4: Certifications + Insurance ───────────────────────────────────

    public function step4(Request $request): View|RedirectResponse
    {
        $agency = $this->requireDraftAgency($request);
        if ($agency instanceof RedirectResponse) return $agency;

        $certifications = $agency->certifications;
        $documents = $agency->documents;

        return view('agency-registration.step4', compact('agency', 'certifications', 'documents'));
    }

    public function storeStep4(Request $request): RedirectResponse
    {
        $agency = $this->ownedAgency($request->user());
        if (!$agency) return redirect()->route('agency.register.step1');

        // Certifications/documents are added via their own AJAX-free forms
        // on this step's view (see AgencyCertificationController /
        // AgencyDocumentController) — this action just advances the wizard.
        $this->onboarding->advanceStep($agency, 4);

        return redirect()->route('agency.register.step5')->with('status', 'step-saved');
    }

    // ─── Step 5: Media + Review + Submit ──────────────────────────────────────

    public function step5(Request $request): View|RedirectResponse
    {
        $agency = $this->requireDraftAgency($request);
        if ($agency instanceof RedirectResponse) return $agency;

        $agency->load(['category', 'services', 'coverage', 'hours', 'certifications', 'documents', 'media']);
        $readinessErrors = $this->onboarding->readinessErrors($agency);

        return view('agency-registration.step5', compact('agency', 'readinessErrors'));
    }

    public function complete(Request $request): RedirectResponse
    {
        $agency = $this->ownedAgency($request->user());
        if (!$agency) return redirect()->route('agency.register.step1');

        $errors = $this->onboarding->readinessErrors($agency);
        if (!empty($errors)) {
            return back()->withErrors(['submission' => implode(' ', $errors)]);
        }

        $this->onboarding->complete($agency);

        activity()
            ->causedBy($request->user())
            ->performedOn($agency)
            ->log('Agency submitted for review');

        return redirect()->route('agency.dashboard')
            ->with('status', 'agency-submitted');
    }

    private function requireDraftAgency(Request $request): Agency|RedirectResponse
    {
        $agency = $this->ownedAgency($request->user());

        if (!$agency) {
            return redirect()->route('agency.register.step1');
        }

        return $agency;
    }

    /**
     * Always queries directly rather than $user->agency — see
     * User::currentAgency() docblock for why relation-property access is
     * avoided here (stale caching across the same object instance's
     * lifetime, which spans this entire multi-step wizard flow).
     */
    private function ownedAgency(\App\Models\User $user): ?Agency
    {
        return Agency::where('user_id', $user->id)->first();
    }
}

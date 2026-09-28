<x-admin-layout title="Matching Engine Settings">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.general.edit') }}" class="hb-link">Settings</a></li>
        <li class="breadcrumb-item active">Matching Engine</li>
    @endslot

    @if(session('status') === 'settings-updated')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i> Matching engine settings updated successfully.
        </div>
    @endif

    @include('admin.settings._nav')

    @php
        $factorLabels = [
            'care_type' => 'Care Type', 'location' => 'Location', 'coverage_area' => 'Coverage Area',
            'distance' => 'Distance', 'budget' => 'Budget Compatibility', 'services_offered' => 'Services Offered',
            'languages' => 'Languages Spoken', 'insurance_accepted' => 'Insurance Accepted',
            'medicaid_medicare' => 'Medicaid / Medicare', 'specialty_care' => 'Specialty Care',
            'memory_care' => 'Memory Care', 'mobility' => 'Mobility Requirements', 'availability' => 'Availability',
            'gender_preference' => 'Gender Preference', 'veteran_benefits' => 'Veteran Benefits',
            'religious_preference' => 'Religious Preference', 'pet_friendly' => 'Pet Friendly',
            'accessibility' => 'Accessibility', 'review_rating' => 'Review Rating',
            'agency_quality' => 'Agency Quality Score', 'verification_status' => 'Agency Verification Status',
        ];
    @endphp

    <form method="POST" action="{{ route('admin.settings.matching.update') }}" novalidate>
        @csrf @method('PUT')

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-1" style="color:var(--hb-gray-900);">Factor Weights</h6>
                        <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:1.25rem;">
                            Relative importance of each factor — these don't need to sum to 100. The engine normalizes across
                            whichever factors actually have data for a given family/agency pair, so a family who left a
                            preference blank never has it silently counted against them.
                        </p>

                        @foreach($factorLabels as $key => $label)
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-7">
                                    <label class="hb-form-label mb-0">{{ $label }}</label>
                                </div>
                                <div class="col-5">
                                    <input type="number" name="weights[{{ $key }}]" class="hb-form-control @error("weights.$key") is-invalid @enderror"
                                           value="{{ old("weights.$key", $weights[$key] ?? 0) }}" min="0" max="100" required>
                                    @error("weights.$key") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);font-size:0.875rem;">Thresholds &amp; Bonus</h6>

                        <div class="mb-3">
                            <label class="hb-form-label">Featured Listing Boost</label>
                            <input type="number" name="featured_boost" class="hb-form-control @error('featured_boost') is-invalid @enderror"
                                   value="{{ old('featured_boost', $featuredBoost) }}" min="0" max="20" required>
                            <small style="font-size:0.75rem;color:var(--hb-gray-600);">Small additive bonus for featured agencies, applied after the main score — not a competing weighted factor.</small>
                            @error('featured_boost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="hb-form-label">Max Distance (miles)</label>
                            <input type="number" name="max_distance_miles" class="hb-form-control @error('max_distance_miles') is-invalid @enderror"
                                   value="{{ old('max_distance_miles', $maxDistance) }}" min="1" max="1000" required>
                            <small style="font-size:0.75rem;color:var(--hb-gray-600);">Agencies farther than this are excluded from results entirely, not just scored lower.</small>
                            @error('max_distance_miles') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-2">
                            <label class="hb-form-label">Minimum Score Threshold</label>
                            <input type="number" name="min_score_threshold" class="hb-form-control @error('min_score_threshold') is-invalid @enderror"
                                   value="{{ old('min_score_threshold', $minScoreThreshold) }}" min="0" max="100" required>
                            <small style="font-size:0.75rem;color:var(--hb-gray-600);">Recommendations below this score are hidden from the family view by default (still visible to advisors).</small>
                            @error('min_score_threshold') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100" style="border-radius:0.75rem;">
                    <i class="bi bi-save me-2"></i>Save Matching Settings
                </button>
            </div>
        </div>
    </form>
</x-admin-layout>

<x-wizard-layout title="Agency Onboarding — Coverage & Hours" :current-step="3">
    <div class="mb-4">
        <h4 class="fw-bold mb-1" style="color:var(--hb-gray-900);font-family:'Fraunces',serif;">Where do you provide care?</h4>
        <p style="color:var(--hb-gray-600);font-size:0.9rem;">Add the cities you serve and your business hours.</p>
    </div>

    <form method="POST" action="{{ route('agency.register.step3.store') }}" novalidate
          x-data="coverageForm({{ $coverage->map(fn($c) => ['city' => $c->city, 'state' => $c->state, 'radius_miles' => $c->radius_miles])->values()->toJson() ?: '[]' }})">
        @csrf

        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-geo-alt me-2"></i>Coverage Areas</h6>

        <template x-for="(area, index) in areas" :key="index">
            <div class="card mb-3" style="border-radius:0.875rem;border:1.5px solid var(--hb-gray-200);">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="hb-form-label">City</label>
                            <input type="text" :name="`coverage[${index}][city]`" x-model="area.city" class="hb-form-control" placeholder="Austin" required>
                        </div>
                        <div class="col-md-3">
                            <label class="hb-form-label">State</label>
                            <input type="text" :name="`coverage[${index}][state]`" x-model="area.state" class="hb-form-control" placeholder="TX" maxlength="2" required>
                        </div>
                        <div class="col-md-4">
                            <label class="hb-form-label">Radius (miles)</label>
                            <input type="number" :name="`coverage[${index}][radius_miles]`" x-model="area.radius_miles" class="hb-form-control" placeholder="25" min="1" max="500">
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeArea(index)" style="border-radius:0.5rem;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <button type="button" class="hb-btn-outline mb-4" style="width:auto;" @click="addArea()">
            <i class="bi bi-plus-lg me-2"></i>Add another city
        </button>

        <hr style="border-color:var(--hb-gray-200);margin:2rem 0;">

        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-clock me-2"></i>Business Hours</h6>

        @php
            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $existingHours = $hours->keyBy('day_of_week');
        @endphp

        @foreach($dayNames as $dayIndex => $dayName)
            @php $h = $existingHours->get($dayIndex); @endphp
            <div class="row g-2 align-items-center mb-2">
                <div class="col-3 col-md-2">
                    <span style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $dayName }}</span>
                </div>
                <div class="col-3 col-md-2">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="closed_{{ $dayIndex }}"
                               name="days[{{ $dayIndex }}][is_closed]" value="1" {{ !$h || $h->is_closed ? 'checked' : '' }}
                               onchange="document.getElementById('hours_{{ $dayIndex }}').style.display = this.checked ? 'none' : 'flex'">
                        <label class="form-check-label" for="closed_{{ $dayIndex }}" style="font-size:0.8rem;color:var(--hb-gray-600);">Closed</label>
                    </div>
                </div>
                <div class="col-6 col-md-8" id="hours_{{ $dayIndex }}" style="display:{{ !$h || $h->is_closed ? 'none' : 'flex' }};gap:0.5rem;">
                    <input type="time" name="days[{{ $dayIndex }}][open_time]" class="hb-form-control" style="max-width:140px;"
                           value="{{ $h?->open_time ? \Illuminate\Support\Carbon::parse($h->open_time)->format('H:i') : '09:00' }}">
                    <span style="align-self:center;color:var(--hb-gray-600);">to</span>
                    <input type="time" name="days[{{ $dayIndex }}][close_time]" class="hb-form-control" style="max-width:140px;"
                           value="{{ $h?->close_time ? \Illuminate\Support\Carbon::parse($h->close_time)->format('H:i') : '17:00' }}">
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('agency.register.step2') }}" class="hb-btn-outline" style="width:auto;padding:0.7rem 1.75rem;text-decoration:none;">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
            <button type="submit" class="hb-btn-primary" style="width:auto;padding:0.7rem 2rem;">
                Continue <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </form>

    @push('scripts')
    <script>
        function coverageForm(initial) {
            return {
                areas: initial.length ? initial : [{ city: '', state: '', radius_miles: 25 }],
                addArea() { this.areas.push({ city: '', state: '', radius_miles: 25 }); },
                removeArea(index) {
                    if (this.areas.length > 1) this.areas.splice(index, 1);
                },
            };
        }
    </script>
    @endpush
</x-wizard-layout>

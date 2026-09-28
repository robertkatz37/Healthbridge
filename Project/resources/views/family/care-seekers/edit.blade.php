<x-family-layout title="{{ $careSeeker->full_name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('family.care-seekers.index') }}" class="hb-link">Care Seekers</a></li>
        <li class="breadcrumb-item active">{{ $careSeeker->full_name }}</li>
    @endslot

    <div class="row g-4">
        {{-- Left: Photo + Quick Actions --}}
        <div class="col-lg-3">
            <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4 text-center">
                    <div class="hb-avatar-upload d-inline-block mb-3">
                        <img src="{{ $careSeeker->photo_url }}" alt="{{ $careSeeker->full_name }}" class="hb-avatar">
                        <form method="POST" action="{{ route('family.care-seekers.photo.store', $careSeeker) }}" enctype="multipart/form-data" id="photoForm">
                            @csrf
                            <label class="hb-avatar-btn" title="Change photo" for="photoInput">
                                <i class="bi bi-camera" style="font-size:0.8rem;"></i>
                            </label>
                            <input type="file" id="photoInput" name="photo" class="d-none" accept="image/*"
                                   onchange="document.getElementById('photoForm').submit()">
                        </form>
                    </div>
                    <div class="fw-bold" style="color:var(--hb-gray-900);">{{ $careSeeker->full_name }}</div>
                    @if($careSeeker->age)
                        <div style="font-size:0.8rem;color:var(--hb-gray-600);">Age {{ $careSeeker->age }}</div>
                    @endif
                </div>
            </div>

            <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-3">
                    <a href="{{ route('family.needs-assessment.start', $careSeeker) }}" class="btn btn-primary btn-sm w-100 mb-2" style="border-radius:0.625rem;">
                        <i class="bi bi-clipboard2-pulse me-1"></i>
                        {{ $careSeeker->needsAssessments()->where('status','completed')->exists() ? 'Retake' : 'Start' }} Needs Assessment
                    </a>
                    <a href="{{ route('family.care-seekers.documents.index', $careSeeker) }}" class="btn btn-outline-secondary btn-sm w-100" style="border-radius:0.625rem;">
                        <i class="bi bi-file-earmark-lock me-1"></i>Documents
                    </a>
                </div>
            </div>

            <div class="card" style="border-radius:1rem;border:1.5px solid #FCA5A5;">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-1 text-danger" style="font-size:0.85rem;">Remove Profile</h6>
                    <p style="font-size:0.775rem;color:var(--hb-gray-600);margin-bottom:0.75rem;">
                        This will permanently delete this care seeker's profile and assessment history.
                    </p>
                    <form method="POST" action="{{ route('family.care-seekers.destroy', $careSeeker) }}"
                          onsubmit="return confirm('Are you sure you want to remove this profile? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger w-100" style="border-radius:0.625rem;">
                            <i class="bi bi-trash me-1"></i>Remove Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: Full Profile Form --}}
        <div class="col-lg-9">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Care Profile</h6>

                    <form method="POST" action="{{ route('family.care-seekers.update', $careSeeker) }}" novalidate>
                        @csrf @method('PUT')

                        <h6 style="font-size:0.8rem;font-weight:700;color:var(--hb-emerald-700);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.75rem;">Basic Info</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="hb-form-label">First name</label>
                                <input type="text" name="first_name" class="hb-form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $careSeeker->first_name) }}" required>
                                @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">Last name</label>
                                <input type="text" name="last_name" class="hb-form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $careSeeker->last_name) }}" required>
                                @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="hb-form-label">Age</label>
                                <input type="number" name="age" class="hb-form-control" value="{{ old('age', $careSeeker->age) }}" min="0" max="130">
                            </div>
                            <div class="col-md-3">
                                <label class="hb-form-label">Gender</label>
                                <input type="text" name="gender" class="hb-form-control" value="{{ old('gender', $careSeeker->gender) }}">
                            </div>
                        </div>

                        <h6 style="font-size:0.8rem;font-weight:700;color:var(--hb-emerald-700);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.75rem;">Care Preferences</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="hb-form-label">Care type needed</label>
                                <select name="care_type_needed" class="hb-form-control">
                                    <option value="">Not sure yet</option>
                                    @foreach(\App\Enums\CareType::cases() as $type)
                                        <option value="{{ $type->value }}" {{ old('care_type_needed', $careSeeker->care_type_needed?->value) === $type->value ? 'selected' : '' }}>
                                            {{ $type->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">Move-in timeline</label>
                                <select name="move_in_timeline" class="hb-form-control">
                                    <option value="">Not specified</option>
                                    @foreach(\App\Enums\MoveInTimeline::cases() as $tl)
                                        <option value="{{ $tl->value }}" {{ old('move_in_timeline', $careSeeker->move_in_timeline?->value) === $tl->value ? 'selected' : '' }}>
                                            {{ $tl->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="hb-form-label">Min. monthly budget</label>
                                <input type="number" name="budget_min" class="hb-form-control" value="{{ old('budget_min', $careSeeker->budget_min) }}" min="0" step="0.01">
                            </div>
                            <div class="col-md-3">
                                <label class="hb-form-label">Max. monthly budget</label>
                                <input type="number" name="budget_max" class="hb-form-control @error('budget_max') is-invalid @enderror" value="{{ old('budget_max', $careSeeker->budget_max) }}" min="0" step="0.01">
                                @error('budget_max') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="hb-form-label">Preferred city</label>
                                <input type="text" name="preferred_city" class="hb-form-control" value="{{ old('preferred_city', $careSeeker->preferred_city) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="hb-form-label">Preferred state</label>
                                <input type="text" name="preferred_state" class="hb-form-control" value="{{ old('preferred_state', $careSeeker->preferred_state) }}" maxlength="2">
                            </div>
                        </div>

                        <h6 style="font-size:0.8rem;font-weight:700;color:var(--hb-emerald-700);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.75rem;">Health &amp; Mobility</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="hb-form-label">Mobility level</label>
                                <select name="mobility" class="hb-form-control">
                                    <option value="">Not specified</option>
                                    @foreach(\App\Enums\MobilityLevel::cases() as $level)
                                        <option value="{{ $level->value }}" {{ old('mobility', $careSeeker->mobility?->value) === $level->value ? 'selected' : '' }}>
                                            {{ $level->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">Memory status</label>
                                <select name="memory_status" class="hb-form-control">
                                    <option value="">Not specified</option>
                                    @foreach(\App\Enums\MemoryStatus::cases() as $status)
                                        <option value="{{ $status->value }}" {{ old('memory_status', $careSeeker->memory_status?->value) === $status->value ? 'selected' : '' }}>
                                            {{ $status->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="hb-form-label">Medical conditions</label>
                                <textarea name="medical_conditions" rows="2" class="hb-form-control">{{ old('medical_conditions', $careSeeker->medical_conditions) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="hb-form-label">Daily activities they need help with (ADLs)</label>
                                <div class="d-flex flex-wrap gap-3 mt-1">
                                    @foreach(\App\Enums\AdlType::cases() as $adl)
                                        @php $checked = in_array($adl->value, old('adl_needs', $careSeeker->adl_needs ?? [])); @endphp
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="adl_needs[]" value="{{ $adl->value }}" id="adl_{{ $adl->value }}" {{ $checked ? 'checked' : '' }}>
                                            <label class="form-check-label" for="adl_{{ $adl->value }}" style="font-size:0.85rem;">{{ $adl->label() }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="hb-form-label">Behavioral concerns</label>
                                <textarea name="behavioral_notes" rows="2" class="hb-form-control">{{ old('behavioral_notes', $careSeeker->behavioral_notes) }}</textarea>
                            </div>
                        </div>

                        <h6 style="font-size:0.8rem;font-weight:700;color:var(--hb-emerald-700);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.75rem;">Languages, Insurance &amp; Benefits</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="hb-form-label">Languages spoken</label>
                                <input type="text" name="languages_text" class="hb-form-control"
                                       value="{{ old('languages_text', is_array($careSeeker->languages) ? implode(', ', $careSeeker->languages) : '') }}"
                                       placeholder="e.g. English, Spanish" onchange="document.getElementById('languagesHidden').value = this.value">
                                <input type="hidden" name="languages_raw" id="languagesHidden">
                                <small style="font-size:0.75rem;color:var(--hb-gray-600);">Separate multiple languages with commas.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">Insurance provider</label>
                                <input type="text" name="insurance_provider" class="hb-form-control" value="{{ old('insurance_provider', $careSeeker->insurance_provider) }}">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" name="has_ltc_insurance" value="1" id="ltc" {{ old('has_ltc_insurance', $careSeeker->has_ltc_insurance) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ltc">Has long-term care insurance</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" name="is_veteran" value="1" id="veteran" {{ old('is_veteran', $careSeeker->is_veteran) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="veteran">Is a veteran or veteran's spouse</label>
                                </div>
                            </div>
                        </div>

                        <h6 style="font-size:0.8rem;font-weight:700;color:var(--hb-emerald-700);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.75rem;">Emergency Contact</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="hb-form-label">Contact name</label>
                                <input type="text" name="emergency_contact_name" class="hb-form-control" value="{{ old('emergency_contact_name', $careSeeker->emergency_contact_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">Contact phone</label>
                                <input type="text" name="emergency_contact_phone" class="hb-form-control" value="{{ old('emergency_contact_phone', $careSeeker->emergency_contact_phone) }}">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;" onclick="prepLanguages()">
                            <i class="bi bi-save me-2"></i>Save Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Convert the comma-separated languages text field into the array
        // format the backend expects (languages[] entries) right before submit.
        document.querySelector('form[action="{{ route('family.care-seekers.update', $careSeeker) }}"]').addEventListener('submit', function (e) {
            const text = this.querySelector('[name="languages_text"]').value;
            const values = text.split(',').map(s => s.trim()).filter(Boolean);
            document.querySelectorAll('input[name="languages[]"]').forEach(el => el.remove());
            values.forEach(v => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'languages[]';
                input.value = v;
                this.appendChild(input);
            });
        });
    </script>
    @endpush
</x-family-layout>

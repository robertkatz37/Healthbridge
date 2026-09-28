<x-advisor-layout title="My Profile">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Profile</li>
    @endslot

    <div class="row g-4">
        {{-- Photo + Availability --}}
        <div class="col-lg-3">
            <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4 text-center">
                    <div class="hb-avatar-upload d-inline-block mb-3">
                        <img src="{{ $advisor->photo_url }}" alt="{{ auth()->user()->name }}" class="hb-avatar">
                        <form method="POST" action="{{ route('advisor.profile.photo.store') }}" enctype="multipart/form-data" id="photoForm">
                            @csrf
                            <label class="hb-avatar-btn" title="Change photo" for="photoInput">
                                <i class="bi bi-camera" style="font-size:0.8rem;"></i>
                            </label>
                            <input type="file" id="photoInput" name="photo" class="d-none" accept="image/*"
                                   onchange="document.getElementById('photoForm').submit()">
                        </form>
                    </div>
                    <div class="fw-bold" style="color:var(--hb-gray-900);">{{ auth()->user()->name }}</div>
                    <div style="font-size:0.8rem;color:var(--hb-gray-600);">{{ auth()->user()->email }}</div>

                    @if($advisor->photo_path)
                        <form method="POST" action="{{ route('advisor.profile.photo.destroy') }}" class="mt-2">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" style="font-size:0.75rem;">Remove photo</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-2" style="color:var(--hb-gray-900);font-size:0.875rem;">Account Settings</h6>
                    <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:1rem;">
                        Name, email, password, and 2FA are managed in your main account settings.
                    </p>
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary btn-sm w-100" style="border-radius:0.625rem;">
                        <i class="bi bi-person-gear me-1"></i> Account Settings &amp; Password
                    </a>
                </div>
            </div>
        </div>

        {{-- Profile Form --}}
        <div class="col-lg-9">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Advisor Profile</h6>

                    <form method="POST" action="{{ route('advisor.profile.update') }}" novalidate>
                        @csrf @method('PUT')

                        <h6 style="font-size:0.8rem;font-weight:700;color:var(--hb-emerald-700);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.75rem;">Personal Information</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="hb-form-label">Phone number</label>
                                <input type="text" name="phone" class="hb-form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $advisor->phone) }}" placeholder="(555) 123-4567">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">License number</label>
                                <input type="text" name="license_number" class="hb-form-control"
                                       value="{{ old('license_number', $advisor->license_number) }}">
                            </div>
                            <div class="col-12">
                                <label class="hb-form-label">Bio</label>
                                <textarea name="bio" rows="3" class="hb-form-control @error('bio') is-invalid @enderror"
                                          placeholder="A short bio families will see...">{{ old('bio', $advisor->bio) }}</textarea>
                                @error('bio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 style="font-size:0.8rem;font-weight:700;color:var(--hb-emerald-700);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.75rem;">Languages &amp; Specialties</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="hb-form-label">Languages spoken</label>
                                <input type="text" id="languagesText" class="hb-form-control"
                                       value="{{ old('languages_text', is_array($advisor->languages) ? implode(', ', $advisor->languages) : '') }}"
                                       placeholder="e.g. English, Spanish">
                                <small style="font-size:0.75rem;color:var(--hb-gray-600);">Separate with commas.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">Specialties</label>
                                <input type="text" id="specialtiesText" class="hb-form-control"
                                       value="{{ old('specialties_text', is_array($advisor->specialties) ? implode(', ', $advisor->specialties) : '') }}"
                                       placeholder="e.g. Memory Care, Veteran Benefits">
                                <small style="font-size:0.75rem;color:var(--hb-gray-600);">Separate with commas.</small>
                            </div>
                            <div class="col-12">
                                <label class="hb-form-label">Certifications</label>
                                <input type="text" name="certifications" class="hb-form-control"
                                       value="{{ old('certifications', $advisor->certifications) }}"
                                       placeholder="e.g. Certified Senior Advisor (CSA)">
                            </div>
                        </div>

                        <h6 style="font-size:0.8rem;font-weight:700;color:var(--hb-emerald-700);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.75rem;">Working Hours &amp; Availability</h6>
                        <div class="mb-3">
                            @php
                                $days = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday'];
                                $existingHours = collect($advisor->working_hours ?? [])->keyBy('day');
                            @endphp
                            @foreach($days as $key => $label)
                                @php $dayData = $existingHours->get($key, ['start' => '09:00', 'end' => '17:00', 'enabled' => in_array($key, ['saturday','sunday']) ? false : true]); @endphp
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-3 col-md-2">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="working_hours[{{ $loop->index }}][enabled]" value="1"
                                                   id="day_{{ $key }}" {{ ($dayData['enabled'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_{{ $key }}" style="font-size:0.85rem;">{{ $label }}</label>
                                        </div>
                                        <input type="hidden" name="working_hours[{{ $loop->index }}][day]" value="{{ $key }}">
                                    </div>
                                    <div class="col-4 col-md-3">
                                        <input type="time" name="working_hours[{{ $loop->index }}][start]" class="hb-form-control" value="{{ $dayData['start'] ?? '09:00' }}">
                                    </div>
                                    <div class="col-4 col-md-3">
                                        <input type="time" name="working_hours[{{ $loop->index }}][end]" class="hb-form-control" value="{{ $dayData['end'] ?? '17:00' }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_available" value="1"
                                   id="isAvailable" {{ old('is_available', $advisor->is_available) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isAvailable">
                                <strong>Currently accepting new leads</strong>
                                <div style="font-size:0.775rem;color:var(--hb-gray-600);">When off, you won't receive new automatic lead assignments.</div>
                            </label>
                        </div>

                        <h6 style="font-size:0.8rem;font-weight:700;color:var(--hb-emerald-700);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.75rem;">Email Signature</h6>
                        <div class="mb-4">
                            <textarea name="email_signature" rows="3" class="hb-form-control"
                                      placeholder="Appended to messages you send to families...">{{ old('email_signature', $advisor->email_signature) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" id="profileSubmit" style="border-radius:0.75rem;">
                            <i class="bi bi-save me-2"></i>Save Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('profileSubmit').closest('form').addEventListener('submit', function () {
            const toArray = (id, name) => {
                const values = document.getElementById(id).value.split(',').map(s => s.trim()).filter(Boolean);
                values.forEach(v => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = v;
                    this.appendChild(input);
                });
            };
            toArray('languagesText', 'languages[]');
            toArray('specialtiesText', 'specialties[]');
        });
    </script>
    @endpush
</x-advisor-layout>

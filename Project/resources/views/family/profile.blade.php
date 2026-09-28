<x-family-layout title="Family Profile">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Profile</li>
    @endslot

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Family Details</h6>

                    <form method="POST" action="{{ route('family.profile.update') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="hb-form-label">Phone number</label>
                            <input type="text" name="phone" class="hb-form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $family->phone) }}" placeholder="(555) 123-4567">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="hb-form-label">Your relationship to the person needing care</label>
                            <input type="text" name="relationship_to_seeker" class="hb-form-control @error('relationship_to_seeker') is-invalid @enderror"
                                   value="{{ old('relationship_to_seeker', $family->relationship_to_seeker) }}" placeholder="e.g. Daughter, Son, Spouse">
                            @error('relationship_to_seeker') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-2" style="color:var(--hb-gray-900);font-size:0.875rem;">Account Settings</h6>
                    <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:1rem;">
                        Your name, email, password, and profile photo are managed in your main account settings.
                    </p>
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary btn-sm w-100" style="border-radius:0.625rem;">
                        <i class="bi bi-person-gear me-1"></i> Account Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-family-layout>

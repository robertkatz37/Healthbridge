<x-family-layout title="Add Care Seeker">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('family.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('family.care-seekers.index') }}" class="hb-link">Care Seekers</a></li>
        <li class="breadcrumb-item active">Add New</li>
    @endslot

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1" style="color:var(--hb-gray-900);">Add a Care Seeker</h6>
                    <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:1.5rem;">
                        Start with the basics. You'll be able to add detailed care preferences next.
                    </p>

                    <form method="POST" action="{{ route('family.care-seekers.store') }}" novalidate>
                        @csrf

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="hb-form-label">First name</label>
                                <input type="text" name="first_name" class="hb-form-control @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name') }}" required>
                                @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">Last name</label>
                                <input type="text" name="last_name" class="hb-form-control @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name') }}" required>
                                @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="hb-form-label">Age</label>
                                <input type="number" name="age" class="hb-form-control @error('age') is-invalid @enderror"
                                       value="{{ old('age') }}" min="0" max="130">
                                @error('age') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">Gender</label>
                                <input type="text" name="gender" class="hb-form-control @error('gender') is-invalid @enderror"
                                       value="{{ old('gender') }}">
                                @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="hb-form-label">Emergency contact name</label>
                                <input type="text" name="emergency_contact_name" class="hb-form-control" value="{{ old('emergency_contact_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">Emergency contact phone</label>
                                <input type="text" name="emergency_contact_phone" class="hb-form-control" value="{{ old('emergency_contact_phone') }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('family.care-seekers.index') }}" class="hb-btn-outline" style="width:auto;padding:0.65rem 1.5rem;text-decoration:none;">
                                Cancel
                            </a>
                            <button type="submit" class="hb-btn-primary" style="width:auto;padding:0.65rem 2rem;">
                                Continue <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-family-layout>

<x-agency-layout title="Agency Profile">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Profile</li>
    @endslot

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Agency Profile</h6>

            <form method="POST" action="{{ route('agency.profile.update') }}" novalidate>
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="hb-form-label">Agency name</label>
                    <input type="text" name="name" class="hb-form-control @error('name') is-invalid @enderror" value="{{ old('name', $agency->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="hb-form-label">Care category</label>
                    <select name="agency_category_id" class="hb-form-control @error('agency_category_id') is-invalid @enderror" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('agency_category_id', $agency->agency_category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('agency_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="hb-form-label">Description</label>
                    <textarea name="description" rows="4" class="hb-form-control @error('description') is-invalid @enderror">{{ old('description', $agency->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="hb-form-label">Phone</label>
                        <input type="text" name="phone" class="hb-form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $agency->phone) }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="hb-form-label">Email</label>
                        <input type="email" name="email" class="hb-form-control @error('email') is-invalid @enderror" value="{{ old('email', $agency->email) }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="hb-form-label">Street address</label>
                    <input type="text" name="address" class="hb-form-control @error('address') is-invalid @enderror" value="{{ old('address', $agency->address) }}">
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="hb-form-label">City</label>
                        <input type="text" name="city" class="hb-form-control @error('city') is-invalid @enderror" value="{{ old('city', $agency->city) }}">
                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="hb-form-label">State</label>
                        <input type="text" name="state" class="hb-form-control @error('state') is-invalid @enderror" value="{{ old('state', $agency->state) }}" maxlength="2">
                        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="hb-form-label">Min. monthly cost</label>
                        <input type="number" name="min_monthly_cost" class="hb-form-control @error('min_monthly_cost') is-invalid @enderror" value="{{ old('min_monthly_cost', $agency->min_monthly_cost) }}" step="0.01" min="0">
                        @error('min_monthly_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small style="font-size:0.75rem;color:var(--hb-gray-600);">Auto-calculated from Pricing tab if left blank.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="hb-form-label">Max. monthly cost</label>
                        <input type="number" name="max_monthly_cost" class="hb-form-control @error('max_monthly_cost') is-invalid @enderror" value="{{ old('max_monthly_cost', $agency->max_monthly_cost) }}" step="0.01" min="0">
                        @error('max_monthly_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">
                    <i class="bi bi-save me-2"></i>Save Changes
                </button>
            </form>
        </div>
    </div>
</x-agency-layout>

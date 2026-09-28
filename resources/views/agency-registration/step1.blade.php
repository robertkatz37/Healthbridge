<x-wizard-layout title="Agency Onboarding — Basic Info" :current-step="1">
    <div class="mb-4">
        <h4 class="fw-bold mb-1" style="color:var(--hb-gray-900);font-family:'Fraunces',serif;">Tell us about your agency</h4>
        <p style="color:var(--hb-gray-600);font-size:0.9rem;">This information will appear on your public listing.</p>
    </div>

    <form method="POST" action="{{ route('agency.register.step1.store') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="name" class="hb-form-label">Agency name</label>
            <input type="text" id="name" name="name" class="hb-form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $agency->name ?? '') }}" placeholder="e.g. Sunrise Senior Living" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="agency_category_id" class="hb-form-label">Care category</label>
            <select id="agency_category_id" name="agency_category_id" class="hb-form-control @error('agency_category_id') is-invalid @enderror" required>
                <option value="">Select a category...</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('agency_category_id', $agency->agency_category_id ?? '') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('agency_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="description" class="hb-form-label">Description</label>
            <textarea id="description" name="description" rows="4" class="hb-form-control @error('description') is-invalid @enderror"
                      placeholder="Describe your agency's approach to care, amenities, and what makes it special...">{{ old('description', $agency->description ?? '') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="phone" class="hb-form-label">Phone number</label>
                <input type="text" id="phone" name="phone" class="hb-form-control @error('phone') is-invalid @enderror"
                       value="{{ old('phone', $agency->phone ?? '') }}" placeholder="(555) 123-4567">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label for="email" class="hb-form-label">Contact email</label>
                <input type="email" id="email" name="email" class="hb-form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $agency->email ?? '') }}" placeholder="contact@youragency.com">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="address" class="hb-form-label">Street address</label>
            <input type="text" id="address" name="address" class="hb-form-control @error('address') is-invalid @enderror"
                   value="{{ old('address', $agency->address ?? '') }}" placeholder="123 Main Street">
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="city" class="hb-form-label">City</label>
                <input type="text" id="city" name="city" class="hb-form-control @error('city') is-invalid @enderror"
                       value="{{ old('city', $agency->city ?? '') }}" placeholder="Austin">
                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label for="state" class="hb-form-label">State</label>
                <input type="text" id="state" name="state" class="hb-form-control @error('state') is-invalid @enderror"
                       value="{{ old('state', $agency->state ?? '') }}" placeholder="TX" maxlength="2" style="text-transform:uppercase;">
                @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="hb-btn-primary" style="width:auto;padding:0.7rem 2rem;">
                Continue <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </form>
</x-wizard-layout>

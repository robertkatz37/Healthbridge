<x-admin-layout title="Branding Settings">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}" class="hb-link">Settings</a></li>
        <li class="breadcrumb-item active">Branding</li>
    @endslot

    @if(session('status') === 'settings-updated')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i> Branding settings updated successfully.
        </div>
    @endif

    @include('admin.settings._nav')

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);font-size:0.875rem;">Platform Logo</h6>
                    @if(!empty($values['branding_logo_path']))
                        <img src="{{ asset('storage/' . $values['branding_logo_path']) }}" alt="Logo"
                             style="max-width:100%;max-height:100px;margin-bottom:1rem;">
                    @else
                        <div style="width:100px;height:100px;background:var(--hb-emerald-100);border-radius:1rem;
                                    display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                            <i class="bi bi-heart-pulse-fill" style="font-size:2rem;color:var(--hb-emerald-700);"></i>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" id="brandingForm">
                        @csrf @method('PUT')
                        <input type="hidden" name="branding_company_name" value="{{ $values['branding_company_name'] ?? '' }}">
                        <input type="hidden" name="branding_from_name" value="{{ $values['branding_from_name'] ?? '' }}">
                        <input type="hidden" name="branding_from_email" value="{{ $values['branding_from_email'] ?? '' }}">
                        <label class="btn btn-outline-primary btn-sm" style="border-radius:0.625rem;">
                            <i class="bi bi-upload me-1"></i>Upload Logo
                            <input type="file" name="logo" class="d-none" accept=".png,.jpg,.jpeg,.svg" onchange="document.getElementById('brandingForm').submit()">
                        </label>
                    </form>
                    <p style="font-size:0.7rem;color:var(--hb-gray-600);margin-top:0.5rem;">PNG, JPG, or SVG — max 2MB</p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Branding Details</h6>

                    <form method="POST" action="{{ route('admin.settings.branding.update') }}" novalidate>
                        @csrf @method('PUT')

                        <div class="mb-3">
                            <label class="hb-form-label">Company name</label>
                            <input type="text" name="branding_company_name" class="hb-form-control @error('branding_company_name') is-invalid @enderror"
                                   value="{{ old('branding_company_name', $values['branding_company_name'] ?? '') }}" required>
                            @error('branding_company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="hb-form-label">"From" name</label>
                                <input type="text" name="branding_from_name" class="hb-form-control @error('branding_from_name') is-invalid @enderror"
                                       value="{{ old('branding_from_name', $values['branding_from_name'] ?? '') }}" required>
                                @error('branding_from_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">"From" email address</label>
                                <input type="email" name="branding_from_email" class="hb-form-control @error('branding_from_email') is-invalid @enderror"
                                       value="{{ old('branding_from_email', $values['branding_from_email'] ?? '') }}" required>
                                @error('branding_from_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <small style="font-size:0.775rem;color:var(--hb-gray-600);display:block;margin-bottom:1rem;">
                            This name and address appear as the sender on every system email (verification, password reset, agency notifications, and future billing emails).
                        </small>

                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">
                            <i class="bi bi-save me-2"></i>Save Branding
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

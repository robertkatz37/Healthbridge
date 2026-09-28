<x-admin-layout title="General Settings">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}" class="hb-link">Settings</a></li>
        <li class="breadcrumb-item active">General</li>
    @endslot

    @if(session('status') === 'settings-updated')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i> Settings updated successfully.
        </div>
    @endif

    @include('admin.settings._nav')

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);max-width:720px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">General Settings</h6>

            <form method="POST" action="{{ route('admin.settings.general.update') }}" novalidate>
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="hb-form-label">Platform name</label>
                    <input type="text" name="platform_name" class="hb-form-control @error('platform_name') is-invalid @enderror"
                           value="{{ old('platform_name', $values['platform_name'] ?? '') }}" required>
                    @error('platform_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="hb-form-label">Support email</label>
                        <input type="email" name="support_email" class="hb-form-control @error('support_email') is-invalid @enderror"
                               value="{{ old('support_email', $values['support_email'] ?? '') }}" required>
                        @error('support_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="hb-form-label">Support phone</label>
                        <input type="text" name="support_phone" class="hb-form-control"
                               value="{{ old('support_phone', $values['support_phone'] ?? '') }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="hb-form-label">Timezone</label>
                    <select name="timezone" class="hb-form-control @error('timezone') is-invalid @enderror">
                        @foreach(['America/New_York','America/Chicago','America/Denver','America/Los_Angeles','UTC'] as $tz)
                            <option value="{{ $tz }}" {{ old('timezone', $values['timezone'] ?? '') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                    @error('timezone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" role="switch" name="maintenance_mode" value="1"
                           id="maintenanceMode" {{ old('maintenance_mode', $values['maintenance_mode'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="maintenanceMode">
                        <strong>Maintenance Mode</strong>
                        <div style="font-size:0.775rem;color:var(--hb-gray-600);">When enabled, the public site shows a maintenance page to visitors.</div>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">
                    <i class="bi bi-save me-2"></i>Save Changes
                </button>
            </form>
        </div>
    </div>
</x-admin-layout>

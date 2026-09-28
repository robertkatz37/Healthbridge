<x-admin-layout title="{{ $template->name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item">
            <a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('admin.settings.index') }}" class="hb-link">Settings</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('admin.settings.templates.index') }}" class="hb-link">Email Templates</a>
        </li>
        <li class="breadcrumb-item active">{{ $template->name }}</li>
    @endslot

    <div class="row g-4">

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius:1rem;">
                <div class="card-body p-4">

                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">
                        {{ $template->name }}
                    </h6>

                    <form method="POST"
                          action="{{ route('admin.settings.templates.update', $template) }}"
                          novalidate>

                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="hb-form-label">
                                Subject Line
                            </label>

                            <input
                                type="text"
                                name="subject"
                                class="hb-form-control @error('subject') is-invalid @enderror"
                                value="{{ old('subject', $template->subject) }}"
                                required>

                            @error('subject')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="hb-form-label">
                                Email Body (HTML)
                            </label>

                            <textarea
                                name="body"
                                rows="14"
                                class="hb-form-control @error('body') is-invalid @enderror"
                                style="font-family:monospace;font-size:.825rem;"
                                required>{{ old('body', $template->body) }}</textarea>

                            @error('body')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-check form-switch mb-4">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="isActive"
                                name="is_active"
                                value="1"
                                {{ old('is_active', $template->is_active) ? 'checked' : '' }}>

                            <label class="form-check-label" for="isActive">

                                <strong>Active</strong>

                                <div class="small text-muted">
                                    When disabled, HealthsBridge will use the built-in default email template instead.
                                </div>

                            </label>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            style="border-radius:.75rem;">

                            <i class="bi bi-save me-2"></i>
                            Save Template

                        </button>

                    </form>

                </div>
            </div>
        </div>

        <div class="col-lg-4">

            <div class="card border-0 shadow-sm"
                 style="border-radius:1rem;position:sticky;top:1rem;">

                <div class="card-body p-4">

                    <h6 class="fw-bold mb-2"
                        style="color:var(--hb-gray-900);font-size:.875rem;">

                        <i class="bi bi-info-circle me-2"
                           style="color:var(--hb-emerald-700);"></i>

                        Available Variables

                    </h6>

                    <p class="text-muted" style="font-size:.825rem;">
                        {{ $template->description }}
                    </p>

                    <div class="alert alert-light border mt-3 mb-0">
                        <strong>Usage</strong>

                        <p class="small text-muted mb-0 mt-2">
                            Use
                            <code>@{{variable_name}}</code>
                            inside the subject or email body.
                            The placeholder will automatically be replaced when the email is sent.
                        </p>
                    </div>

                </div>

            </div>

        </div>

    </div>

</x-admin-layout>
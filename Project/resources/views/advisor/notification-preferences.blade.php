<x-advisor-layout title="Notification Preferences">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('advisor.notifications.index') }}" class="hb-link">Notifications</a></li>
        <li class="breadcrumb-item active">Preferences</li>
    @endslot

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);max-width:640px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Notification Preferences</h6>
            <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:1.5rem;">
                Choose how you'd like to be notified for each event. In-app notifications always appear in your Notifications center regardless of this setting unless turned off here.
            </p>

            <form method="POST" action="{{ route('advisor.notification-preferences.update') }}">
                @csrf @method('PUT')

                @foreach($events as $key => $label)
                    <div class="d-flex justify-content-between align-items-center py-3" style="border-bottom:1px solid var(--hb-gray-200);">
                        <span style="font-size:0.9rem;color:var(--hb-gray-900);">{{ $label }}</span>
                        <div class="d-flex gap-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="{{ $key }}_mail" value="1"
                                       id="{{ $key }}_mail" {{ data_get($preferences, "{$key}.mail", true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $key }}_mail" style="font-size:0.8rem;">Email</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="{{ $key }}_database" value="1"
                                       id="{{ $key }}_database" {{ data_get($preferences, "{$key}.database", true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $key }}_database" style="font-size:0.8rem;">In-App</label>
                            </div>
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary mt-4" style="border-radius:0.75rem;">
                    <i class="bi bi-save me-2"></i>Save Preferences
                </button>
            </form>
        </div>
    </div>
</x-advisor-layout>

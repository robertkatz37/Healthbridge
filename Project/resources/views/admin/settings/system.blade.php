<x-admin-layout title="System Information">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}" class="hb-link">Settings</a></li>
        <li class="breadcrumb-item active">System</li>
    @endslot

    @include('admin.settings._nav')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Environment</h6>
                    <div class="row g-3">
                        @foreach($info as $label => $value)
                            <div class="col-md-6">
                                <div style="background:var(--hb-gray-50);border-radius:0.75rem;padding:0.875rem;">
                                    <div style="font-size:0.7rem;color:var(--hb-gray-600);text-transform:uppercase;letter-spacing:0.03em;">
                                        {{ str_replace('_', ' ', $label) }}
                                    </div>
                                    <div style="font-size:0.9rem;font-weight:700;color:var(--hb-gray-900);">
                                        @if(is_bool($value))
                                            {{ $value ? 'Enabled' : 'Disabled' }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Queue Health</h6>
                    <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                        <span style="font-size:0.85rem;color:var(--hb-gray-600);">Pending Jobs</span>
                        <span class="fw-bold" style="color:var(--hb-gray-900);">{{ $counts['pending_jobs'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span style="font-size:0.85rem;color:var(--hb-gray-600);">Failed Jobs</span>
                        <span class="fw-bold" style="color:{{ $counts['failed_jobs'] > 0 ? 'var(--hb-danger)' : 'var(--hb-gray-900)' }};">{{ $counts['failed_jobs'] }}</span>
                    </div>
                    <a href="{{ route('admin.settings.failed-emails.index') }}" class="btn btn-outline-secondary btn-sm w-100 mt-3" style="border-radius:0.625rem;">
                        View Queue Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

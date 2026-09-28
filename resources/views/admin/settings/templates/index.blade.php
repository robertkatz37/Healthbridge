<x-admin-layout title="Email Templates">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}" class="hb-link">Settings</a></li>
        <li class="breadcrumb-item active">Email Templates</li>
    @endslot

    @if(session('status') === 'template-updated')
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i> Email template updated successfully.
        </div>
    @endif

    @include('admin.settings._nav')

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            <table class="table mb-0" style="font-size:0.875rem;">
                <thead style="background:var(--hb-gray-50);">
                    <tr>
                        <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Template</th>
                        <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Subject</th>
                        <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                        <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $template)
                        <tr style="border-bottom:1px solid var(--hb-gray-200);">
                            <td class="px-4 py-3 fw-bold" style="color:var(--hb-gray-900);">{{ $template->name }}</td>
                            <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ Str::limit($template->subject, 50) }}</td>
                            <td class="px-4 py-3">
                                @if($template->is_active)
                                    <span class="hb-badge-verified">Active</span>
                                @else
                                    <span style="font-size:0.7rem;font-weight:600;background:var(--hb-gray-200);color:var(--hb-gray-600);padding:0.2rem 0.6rem;border-radius:999px;">Inactive (using default)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end">
                                <a href="{{ route('admin.settings.templates.edit', $template) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>

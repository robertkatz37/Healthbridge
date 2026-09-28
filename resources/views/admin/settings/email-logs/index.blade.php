<x-admin-layout title="Email Logs">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}" class="hb-link">Settings</a></li>
        <li class="breadcrumb-item active">Email Logs</li>
    @endslot

    @include('admin.settings._nav')

    <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.settings.email-logs.index') }}" class="d-flex align-items-end gap-2">
                <div>
                    <label class="hb-form-label">Status</label>
                    <select name="status" class="hb-form-control" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-envelope" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No emails logged yet.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">To</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Subject</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Sent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3">{{ $log->to_address }}</td>
                                    <td class="px-4 py-3">{{ $log->subject ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if($log->status === 'sent')
                                            <span class="hb-badge-verified"><i class="bi bi-check-circle me-1"></i>Sent</span>
                                        @else
                                            <span style="font-size:0.7rem;font-weight:600;background:#FEF2F2;color:#991B1B;padding:0.2rem 0.6rem;border-radius:999px;" title="{{ $log->error_message }}">
                                                <i class="bi bi-x-circle me-1"></i>Failed
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $log->sent_at?->diffForHumans() ?? $log->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">
                        {{ $logs->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>

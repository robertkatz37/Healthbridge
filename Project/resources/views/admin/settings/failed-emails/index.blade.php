<x-admin-layout title="Failed Emails & Email Queue">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}" class="hb-link">Settings</a></li>
        <li class="breadcrumb-item active">Failed Emails</li>
    @endslot

    @if(session('status'))
        @php
            $msgs = ['job-retried' => 'Job re-queued for retry.', 'job-deleted' => 'Failed job deleted.', 'queue-processed' => 'Queue processed.'];
        @endphp
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i> {{ $msgs[session('status')] ?? session('status') }}
        </div>
    @endif

    @include('admin.settings._nav')

    <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h6 class="fw-bold mb-1" style="color:var(--hb-gray-900);">
                    <i class="bi bi-hourglass-split me-2" style="color:var(--hb-warning);"></i>Email Queue
                </h6>
                <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">
                    {{ $pendingCount }} email(s) pending in the queue. A queue worker (<code>php artisan queue:work</code>) processes these automatically in production.
                </p>
            </div>
            <form method="POST" action="{{ route('admin.settings.failed-emails.process-queue') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm" style="border-radius:0.625rem;" {{ $pendingCount === 0 ? 'disabled' : '' }}>
                    <i class="bi bi-play-fill me-1"></i>Process Queue Now
                </button>
            </form>
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-header py-3 px-4" style="background:none;border-bottom:1px solid var(--hb-gray-200);">
            <h6 class="mb-0 fw-bold" style="color:var(--hb-gray-900);">Failed Jobs</h6>
        </div>
        <div class="card-body p-0">
            @if($failedJobs->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-check2-circle" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No failed jobs.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Job</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Failed At</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($failedJobs as $job)
                                @php
                                    $payload = json_decode($job->payload, true);
                                    $jobName = $payload['displayName'] ?? 'Unknown job';
                                @endphp
                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                    <td class="px-4 py-3">
                                        <div class="fw-bold" style="color:var(--hb-gray-900);font-size:0.8rem;">{{ Str::afterLast($jobName, '\\') }}</div>
                                        <div style="font-size:0.75rem;color:var(--hb-gray-600);max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $job->exception }}">
                                            {{ Str::limit($job->exception, 100) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ \Illuminate\Support\Carbon::parse($job->failed_at)->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <form method="POST" action="{{ route('admin.settings.failed-emails.retry', $job->uuid) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">
                                                <i class="bi bi-arrow-clockwise"></i> Retry
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.settings.failed-emails.destroy', $job->uuid) }}" class="d-inline" onsubmit="return confirm('Delete this failed job permanently?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($failedJobs->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">
                        {{ $failedJobs->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>

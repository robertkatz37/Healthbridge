<x-admin-layout title="Agency Applications">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Agencies</li>
    @endslot

    @if(session('status'))
        @php
            $msgs = [
                'agency-approved' => 'Agency approved and published.',
                'agency-rejected' => 'Agency application rejected.',
                'agency-changes-requested' => 'Changes requested — the owner has been notified.',
                'agency-suspended' => 'Agency suspended.',
                'agency-reactivated' => 'Agency reactivated.',
                'note-added' => 'Internal note added.',
            ];
        @endphp
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i> {{ $msgs[session('status')] ?? session('status') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.agencies.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="hb-form-label">Search</label>
                    <div class="hb-input-icon-wrap">
                        <i class="bi bi-search hb-input-icon" style="font-size:0.9rem;"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="hb-form-control" placeholder="Agency name...">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="hb-form-label">Status</label>
                    <select name="status" class="hb-form-control">
                        <option value="">Pending Review Queue (default)</option>
                        @foreach(['draft','pending_review','changes_requested','approved','published','rejected','suspended'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;flex:1;">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    @if(request('search') || request('status'))
                        <a href="{{ route('admin.agencies.index') }}" class="btn btn-outline-secondary" style="border-radius:0.75rem;">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-header d-flex align-items-center justify-content-between py-3 px-4" style="background:none;border-bottom:1px solid var(--hb-gray-200);">
            <h6 class="mb-0 fw-bold" style="color:var(--hb-gray-900);">
                @if(!request('status'))
                    Pending Review Queue
                @else
                    {{ ucfirst(str_replace('_', ' ', request('status'))) }} Agencies
                @endif
                <span style="font-size:0.75rem;font-weight:400;color:var(--hb-gray-600);margin-left:0.5rem;">
                    {{ number_format($agencies->total()) }} total
                </span>
            </h6>
        </div>
        <div class="card-body p-0">
            @if($agencies->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No agencies found matching your filters.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Agency</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Owner</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Submitted</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agencies as $agency)
                                @php
                                    $statusColors = [
                                        'draft' => ['bg' => 'var(--hb-gray-200)', 'text' => 'var(--hb-gray-600)'],
                                        'pending_review' => ['bg' => '#FFFBEB', 'text' => '#92400E'],
                                        'changes_requested' => ['bg' => '#FFFBEB', 'text' => '#92400E'],
                                        'approved' => ['bg' => 'var(--hb-emerald-100)', 'text' => 'var(--hb-emerald-700)'],
                                        'published' => ['bg' => 'var(--hb-emerald-100)', 'text' => 'var(--hb-emerald-700)'],
                                        'rejected' => ['bg' => '#FEF2F2', 'text' => '#991B1B'],
                                        'suspended' => ['bg' => '#FEF2F2', 'text' => '#991B1B'],
                                    ];
                                    $c = $statusColors[$agency->status->value] ?? ['bg' => 'var(--hb-gray-200)', 'text' => 'var(--hb-gray-600)'];
                                @endphp
                                <tr style="border-bottom:1px solid var(--hb-gray-200);" class="align-middle">
                                    <td class="px-4 py-3">
                                        <div class="fw-bold" style="color:var(--hb-gray-900);">{{ $agency->name }}</div>
                                        <div style="font-size:0.775rem;color:var(--hb-gray-600);">{{ $agency->category?->name }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div>{{ $agency->user->name }}</div>
                                        <div style="font-size:0.775rem;color:var(--hb-gray-600);">{{ $agency->user->email }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span style="font-size:0.75rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.2rem 0.6rem;border-radius:999px;">
                                            {{ $agency->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">
                                        {{ $agency->updated_at->diffForHumans() }}
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('admin.agencies.show', $agency) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">
                                            <i class="bi bi-eye"></i> Review
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($agencies->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">
                        {{ $agencies->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-admin-layout>

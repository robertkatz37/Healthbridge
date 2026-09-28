<x-advisor-layout title="Lead Inbox">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Lead Inbox</li>
    @endslot

    {{-- Search & Advanced Filters --}}
    <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('advisor.leads.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="hb-form-label">Search family</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="hb-form-control" placeholder="Family name...">
                </div>
                <div class="col-md-2">
                    <label class="hb-form-label">Status</label>
                    <select name="status" class="hb-form-control">
                        <option value="">All</option>
                        @foreach(\App\Enums\LeadStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="hb-form-label">Source</label>
                    <select name="source" class="hb-form-control">
                        <option value="">All</option>
                        @foreach(\App\Enums\LeadSource::cases() as $source)
                            <option value="{{ $source->value }}" {{ request('source') === $source->value ? 'selected' : '' }}>{{ $source->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="hb-form-label">Care Type</label>
                    <select name="care_type" class="hb-form-control">
                        <option value="">All</option>
                        @foreach(\App\Enums\CareType::cases() as $type)
                            <option value="{{ $type->value }}" {{ request('care_type') === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="hb-form-label">Move-in Timeline</label>
                    <select name="move_in_timeline" class="hb-form-control">
                        <option value="">All</option>
                        @foreach(\App\Enums\MoveInTimeline::cases() as $timeline)
                            <option value="{{ $timeline->value }}" {{ request('move_in_timeline') === $timeline->value ? 'selected' : '' }}>{{ $timeline->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary" style="border-radius:0.625rem;flex:1;">
                        <i class="bi bi-filter"></i>
                    </button>
                </div>

                <div class="col-md-2">
                    <label class="hb-form-label">Min Budget</label>
                    <input type="number" name="budget_min" value="{{ request('budget_min') }}" class="hb-form-control" placeholder="$">
                </div>
                <div class="col-md-2">
                    <label class="hb-form-label">Max Budget</label>
                    <input type="number" name="budget_max" value="{{ request('budget_max') }}" class="hb-form-control" placeholder="$">
                </div>
                <div class="col-md-2">
                    <label class="hb-form-label">City</label>
                    <input type="text" name="city" value="{{ request('city') }}" class="hb-form-control">
                </div>
                <div class="col-md-2">
                    <label class="hb-form-label">State</label>
                    <input type="text" name="state" value="{{ request('state') }}" class="hb-form-control" maxlength="2">
                </div>
                @if($teamAdvisors->isNotEmpty())
                    <div class="col-md-2">
                        <label class="hb-form-label">Advisor</label>
                        <select name="advisor_id" class="hb-form-control">
                            <option value="">All (team)</option>
                            @foreach($teamAdvisors as $teamAdvisor)
                                <option value="{{ $teamAdvisor->id }}" {{ request('advisor_id') == $teamAdvisor->id ? 'selected' : '' }}>{{ $teamAdvisor->user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <label class="hb-form-label">Received From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="hb-form-control">
                </div>
                <div class="col-md-2">
                    <label class="hb-form-label">Received To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="hb-form-control">
                </div>
            </form>
            @if(request()->anyFilled(['search', 'status', 'source', 'date_from', 'date_to', 'care_type', 'budget_min', 'budget_max', 'city', 'state', 'move_in_timeline', 'advisor_id']))
                <a href="{{ route('advisor.leads.index') }}" class="hb-link d-inline-block mt-2" style="font-size:0.8rem;">
                    <i class="bi bi-x-lg me-1"></i>Clear filters
                </a>
            @endif
        </div>
    </div>

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-header d-flex align-items-center justify-content-between py-3 px-4" style="background:none;border-bottom:1px solid var(--hb-gray-200);">
            <h6 class="mb-0 fw-bold" style="color:var(--hb-gray-900);">
                Leads
                <span style="font-size:0.75rem;font-weight:400;color:var(--hb-gray-600);margin-left:0.5rem;">
                    {{ number_format($leads->total()) }} total
                </span>
            </h6>
        </div>
        <div class="card-body p-0">
            @if($leads->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                    <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No leads found matching your filters.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:0.875rem;">
                        <thead style="background:var(--hb-gray-50);">
                            <tr>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Family</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Source</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Status</th>
                                <th class="px-4 py-3" style="color:var(--hb-gray-600);font-weight:600;">Received</th>
                                <th class="px-4 py-3 text-end" style="color:var(--hb-gray-600);font-weight:600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leads as $lead)
                                @php
                                    $statusColors = [
                                        'new' => ['bg' => 'var(--hb-gray-200)', 'text' => 'var(--hb-gray-600)'],
                                        'assigned' => ['bg' => '#EFF6FF', 'text' => '#1E40AF'],
                                        'contacted' => ['bg' => '#EFF6FF', 'text' => '#1E40AF'],
                                        'assessment_reviewed' => ['bg' => '#FFFBEB', 'text' => '#92400E'],
                                        'shortlisted' => ['bg' => '#FFFBEB', 'text' => '#92400E'],
                                        'tour_scheduled' => ['bg' => 'var(--hb-emerald-100)', 'text' => 'var(--hb-emerald-700)'],
                                        'follow_up' => ['bg' => '#FFFBEB', 'text' => '#92400E'],
                                        'move_in_confirmed' => ['bg' => 'var(--hb-emerald-100)', 'text' => 'var(--hb-emerald-700)'],
                                        'converted' => ['bg' => 'var(--hb-emerald-100)', 'text' => 'var(--hb-emerald-700)'],
                                        'closed_lost' => ['bg' => '#FEF2F2', 'text' => '#991B1B'],
                                    ];
                                    $c = $statusColors[$lead->status->value] ?? ['bg' => 'var(--hb-gray-200)', 'text' => 'var(--hb-gray-600)'];
                                @endphp
                                <tr style="border-bottom:1px solid var(--hb-gray-200);" class="align-middle">
                                    <td class="px-4 py-3 fw-bold" style="color:var(--hb-gray-900);">{{ $lead->family_name }}</td>
                                    <td class="px-4 py-3">{{ $lead->source->label() }}</td>
                                    <td class="px-4 py-3">
                                        <span style="font-size:0.75rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.2rem 0.6rem;border-radius:999px;">
                                            {{ $lead->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3" style="color:var(--hb-gray-600);">{{ $lead->created_at->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('advisor.leads.show', $lead) }}" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($leads->hasPages())
                    <div class="px-4 py-3" style="border-top:1px solid var(--hb-gray-200);">
                        {{ $leads->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-advisor-layout>

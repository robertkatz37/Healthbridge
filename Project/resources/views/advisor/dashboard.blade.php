<x-advisor-layout title="Advisor Dashboard">

    <div class="card mb-4" style="border-radius:1rem;border:none;background:linear-gradient(135deg,var(--hb-emerald-900) 0%,var(--hb-emerald-700) 100%);color:white;box-shadow:0 4px 20px rgba(11,110,79,0.25);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width:52px;height:52px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-person-badge" style="font-size:1.4rem;color:white;"></i>
                </div>
                <div>
                    <p style="font-size:0.8rem;opacity:0.8;margin-bottom:0.1rem;">Advisor Dashboard</p>
                    <h4 class="fw-bold mb-0" style="font-family:'Fraunces',serif;">{{ auth()->user()->name }}</h4>
                    @if($advisor->territories()->exists())
                        <div style="font-size:0.8rem;opacity:0.7;">{{ $advisor->territories()->count() }} territories covered</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        @php
            $kpis = [
                ['icon' => 'bi-inbox', 'label' => 'Total Leads', 'value' => $summary['total_leads'], 'color' => 'var(--hb-emerald-700)'],
                ['icon' => 'bi-star', 'label' => 'New Leads', 'value' => $summary['new_leads'], 'color' => 'var(--hb-gray-600)'],
                ['icon' => 'bi-lightning', 'label' => 'Open Leads', 'value' => $summary['open_leads'], 'color' => 'var(--hb-warning)'],
                ['icon' => 'bi-activity', 'label' => 'Active Leads', 'value' => $summary['active_leads'], 'color' => '#3B82F6'],
                ['icon' => 'bi-exclamation-triangle', 'label' => 'Overdue Leads', 'value' => $summary['overdue_leads'], 'color' => 'var(--hb-danger)'],
                ['icon' => 'bi-check2-circle', 'label' => 'Converted Leads', 'value' => $summary['converted_leads'], 'color' => 'var(--hb-success)'],
                ['icon' => 'bi-graph-up', 'label' => 'Conversion Rate', 'value' => $summary['conversion_rate'] . '%', 'color' => 'var(--hb-emerald-700)'],
                ['icon' => 'bi-clock-history', 'label' => 'Avg. Response Time', 'value' => $summary['average_response_time_hours'] !== null ? $summary['average_response_time_hours'] . 'h' : '—', 'color' => 'var(--hb-gray-600)'],
                ['icon' => 'bi-calendar-day', 'label' => 'Tours Today', 'value' => $summary['tours_today'], 'color' => 'var(--hb-gold-500)'],
                ['icon' => 'bi-calendar-check', 'label' => 'Tours Upcoming', 'value' => $summary['tours_upcoming'], 'color' => 'var(--hb-gold-500)'],
                ['icon' => 'bi-list-task', 'label' => 'Pending Tasks', 'value' => $summary['tasks_pending'], 'color' => 'var(--hb-warning)'],
                ['icon' => 'bi-check2-square', 'label' => 'Completed Tasks', 'value' => $summary['tasks_completed'], 'color' => 'var(--hb-success)'],
            ];
        @endphp
        @foreach($kpis as $kpi)
            <div class="col-6 col-lg-3">
                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <div style="width:42px;height:42px;background:{{ $kpi['color'] }}1a;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;">
                            <i class="bi {{ $kpi['icon'] }}" style="font-size:1.15rem;color:{{ $kpi['color'] }};"></i>
                        </div>
                        <div class="fw-bold" style="font-size:1.6rem;color:var(--hb-gray-900);line-height:1;">{{ $kpi['value'] }}</div>
                        <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.25rem;">{{ $kpi['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Referral KPIs (Phase 13) — distinct from the Lead-level KPIs
         above; a Lead can have zero or several Referrals, so these
         numbers deliberately don't just mirror the Lead conversion rate. --}}
    <div class="row g-3 mb-4">
        @php
            $referralKpiCards = [
                ['icon' => 'bi-send', 'label' => 'Total Referrals', 'value' => $referralKpis['total'], 'color' => 'var(--hb-emerald-700)'],
                ['icon' => 'bi-hourglass-split', 'label' => 'Pending', 'value' => $referralKpis['pending'], 'color' => 'var(--hb-warning)'],
                ['icon' => 'bi-check-circle', 'label' => 'Accepted', 'value' => $referralKpis['accepted'], 'color' => '#3B82F6'],
                ['icon' => 'bi-graph-up-arrow', 'label' => 'Referral Conversion Rate', 'value' => $referralKpis['conversion_rate'] . '%', 'color' => 'var(--hb-success)'],
            ];
        @endphp
        @foreach($referralKpiCards as $kpi)
            <div class="col-6 col-lg-3">
                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <div style="width:42px;height:42px;background:{{ $kpi['color'] }}1a;border-radius:0.75rem;display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;">
                            <i class="bi {{ $kpi['icon'] }}" style="font-size:1.15rem;color:{{ $kpi['color'] }};"></i>
                        </div>
                        <div class="fw-bold" style="font-size:1.6rem;color:var(--hb-gray-900);line-height:1;">{{ $kpi['value'] }}</div>
                        <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.25rem;">{{ $kpi['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($summary['tasks_overdue'] > 0)
        <div class="hb-alert hb-alert-warning mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i>
            You have <strong>{{ $summary['tasks_overdue'] }}</strong> overdue {{ Str::plural('task', $summary['tasks_overdue']) }}.
            <a href="{{ route('advisor.calendar') }}" class="hb-link">View calendar</a>
        </div>
    @endif

    @if($teamSummary)
        <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Team Overview</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div style="font-size:1.4rem;font-weight:700;color:var(--hb-emerald-700);">{{ $teamSummary['team_size'] }}</div>
                        <div style="font-size:0.8rem;color:var(--hb-gray-600);">Team Members</div>
                    </div>
                    <div class="col-md-4">
                        <div style="font-size:1.4rem;font-weight:700;color:var(--hb-emerald-700);">{{ $teamSummary['total_open_leads'] }}</div>
                        <div style="font-size:0.8rem;color:var(--hb-gray-600);">Team's Open Leads</div>
                    </div>
                    <div class="col-md-4">
                        <div style="font-size:1.4rem;font-weight:700;color:var(--hb-warning);">{{ $teamSummary['unassigned_leads'] }}</div>
                        <div style="font-size:0.8rem;color:var(--hb-gray-600);">Unassigned Platform-Wide</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($leadsNeedingRecommendations->isNotEmpty())
        <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);border-left:4px solid var(--hb-gold-500) !important;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-1" style="color:var(--hb-gray-900);">
                    <i class="bi bi-stars me-2" style="color:var(--hb-gold-500);"></i>Recommendations Ready to Generate
                </h6>
                <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:0.75rem;">
                    These families have completed their Needs Assessment — generate recommendations to start building their shortlist.
                </p>
                @foreach($leadsNeedingRecommendations as $lead)
                    <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                        <div>
                            <a href="{{ route('advisor.leads.show', $lead) }}" class="text-decoration-none">
                                <span style="font-size:0.9rem;font-weight:600;color:var(--hb-gray-900);">{{ $lead->family_name }}</span>
                            </a>
                        </div>
                        <form method="POST" action="{{ route('advisor.leads.recommendations.generate', $lead) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.5rem;">
                                <i class="bi bi-stars me-1"></i>Generate Recommendations
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Recent Leads</h6>
                        <a href="{{ route('advisor.leads.index') }}" class="hb-link" style="font-size:0.8rem;">View all</a>
                    </div>

                    @forelse($recentLeads as $lead)
                        <a href="{{ route('advisor.leads.show', $lead) }}" class="d-flex align-items-center justify-content-between py-2 text-decoration-none" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div>
                                <div style="font-size:0.9rem;font-weight:600;color:var(--hb-gray-900);">{{ $lead->family_name }}</div>
                                <div style="font-size:0.775rem;color:var(--hb-gray-600);">{{ $lead->created_at->diffForHumans() }}</div>
                            </div>
                            <span class="hb-badge-verified">{{ $lead->status->label() }}</span>
                        </a>
                    @empty
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size:2rem;color:var(--hb-gray-200);"></i>
                            <p class="mt-2 mb-0" style="font-size:0.875rem;color:var(--hb-gray-600);">No leads yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Upcoming Tasks</h6>
                        <a href="{{ route('advisor.calendar') }}" class="hb-link" style="font-size:0.8rem;">Calendar</a>
                    </div>

                    @forelse($upcomingTasks as $task)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div>
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $task->title }}</div>
                                <div style="font-size:0.775rem;color:{{ $task->due_at->isPast() ? 'var(--hb-danger)' : 'var(--hb-gray-600)' }};">
                                    Due {{ $task->due_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="bi bi-check2-circle" style="font-size:2rem;color:var(--hb-gray-200);"></i>
                            <p class="mt-2 mb-0" style="font-size:0.875rem;color:var(--hb-gray-600);">No pending tasks.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Recent Activity</h6>
                        <a href="{{ route('advisor.activity') }}" class="hb-link" style="font-size:0.8rem;">View all</a>
                    </div>
                    @forelse($recentActivity as $entry)
                        <div class="d-flex gap-3 py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div style="width:28px;height:28px;background:{{ $entry['color'] }}1a;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi {{ $entry['icon'] }}" style="font-size:0.7rem;color:{{ $entry['color'] }};"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:0.85rem;color:var(--hb-gray-900);">
                                    {{ $entry['title'] }}
                                    @if($entry['family_name'])
                                        <span style="color:var(--hb-gray-600);">— {{ $entry['family_name'] }}</span>
                                    @endif
                                </div>
                                <div style="font-size:0.7rem;color:var(--hb-gray-600);">{{ $entry['at']->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No activity yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        {{-- Upcoming Tours --}}
        <div class="col-lg-6">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Upcoming Tours</h6>
                        <a href="{{ route('advisor.tours.index') }}" class="hb-link" style="font-size:0.8rem;">View all</a>
                    </div>
                    @forelse($upcomingTours as $tour)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div>
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $tour->agency->name }}</div>
                                <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $tour->lead?->family_name }} &middot; {{ $tour->requested_date->format('M d, Y') }}</div>
                            </div>
                            <span class="hb-badge-verified">{{ $tour->status->label() }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No upcoming tours.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Pending Follow-ups --}}
        <div class="col-lg-6">
            <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Pending Follow-ups</h6>
                        <a href="{{ route('advisor.referrals.index', ['filter' => 'all']) }}" class="hb-link" style="font-size:0.8rem;">View all</a>
                    </div>
                    @forelse($pendingFollowUps as $referral)
                        <a href="{{ route('advisor.referrals.show', $referral) }}" class="text-decoration-none">
                            <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                                <div>
                                    <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $referral->agency->name }}</div>
                                    <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $referral->careSeeker?->full_name }}</div>
                                </div>
                                <span style="font-size:0.7rem;font-weight:700;color:{{ $referral->priority->color() }};text-transform:uppercase;">{{ $referral->priority->label() }}</span>
                            </div>
                        </a>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No referrals awaiting follow-up.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-advisor-layout>

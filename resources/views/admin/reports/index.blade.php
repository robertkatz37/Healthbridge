<x-admin-layout title="Reports & Analytics">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Reports & Analytics</li>
    @endslot

    {{-- Summary KPIs --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Total Agencies', 'value' => $summary['total_agencies'], 'sub' => $summary['published_agencies'] . ' published'],
            ['label' => 'Total Families', 'value' => $summary['total_families'], 'sub' => $summary['total_care_seekers'] . ' care seekers'],
            ['label' => 'Total Advisors', 'value' => $summary['total_advisors'], 'sub' => $summary['active_advisors'] . ' active'],
            ['label' => 'Total Leads', 'value' => $summary['total_leads'], 'sub' => $summary['conversion_rate'] . '% converted'],
        ] as $stat)
            <div class="col-6 col-lg-3">
                <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <div class="fw-bold" style="font-size:1.6rem;color:var(--hb-gray-900);line-height:1;">{{ number_format($stat['value']) }}</div>
                        <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.25rem;">{{ $stat['label'] }}</div>
                        <div style="font-size:0.7rem;color:var(--hb-emerald-700);margin-top:0.25rem;">{{ $stat['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        {{-- Agency Status Breakdown --}}
        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Agencies by Status</h6>
                    @forelse($agencyStatusCounts as $status => $count)
                        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <span style="font-size:0.875rem;color:var(--hb-gray-900);">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                            <span class="fw-bold" style="color:var(--hb-emerald-700);">{{ $count }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No agencies yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Lead Pipeline Breakdown --}}
        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Leads by Pipeline Stage</h6>
                    @forelse($leadStatusCounts as $status => $count)
                        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <span style="font-size:0.875rem;color:var(--hb-gray-900);">{{ \App\Enums\LeadStatus::from($status)->label() }}</span>
                            <span class="fw-bold" style="color:var(--hb-emerald-700);">{{ $count }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No leads yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Agency Category Breakdown --}}
        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Agencies by Category</h6>
                    @forelse($agencyCategoryCounts as $category)
                        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <span style="font-size:0.875rem;color:var(--hb-gray-900);">{{ $category->name }}</span>
                            <span class="fw-bold" style="color:var(--hb-emerald-700);">{{ $category->agencies_count }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No categories.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Matching Engine Stats --}}
        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">
                        <i class="bi bi-stars me-2" style="color:var(--hb-gold-500);"></i>Matching Engine
                    </h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <div style="font-size:1.3rem;font-weight:700;color:var(--hb-gray-900);">{{ number_format($matchResultStats['total_generated']) }}</div>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">Recommendations Generated</div>
                        </div>
                        <div class="col-6">
                            <div style="font-size:1.3rem;font-weight:700;color:var(--hb-gray-900);">{{ $matchResultStats['average_score'] }}%</div>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">Average Match Score</div>
                        </div>
                        <div class="col-6">
                            <div style="font-size:1.3rem;font-weight:700;color:var(--hb-gray-900);">{{ number_format($matchResultStats['total_shortlisted']) }}</div>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">Family Shortlisted</div>
                        </div>
                        <div class="col-6">
                            <div style="font-size:1.3rem;font-weight:700;color:var(--hb-gray-900);">{{ number_format($matchResultStats['total_advisor_approved']) }}</div>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">Advisor Approved</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.settings.matching.edit') }}" class="hb-link d-inline-block mt-3" style="font-size:0.8rem;">
                        <i class="bi bi-sliders me-1"></i>Adjust Matching Weights
                    </a>
                </div>
            </div>
        </div>

        {{-- Referral Analytics (Phase 13) --}}
        <div class="col-12">
            <div class="row g-3 mb-1">
                @php
                    $referralKpiCards = [
                        ['label' => 'Total Referrals', 'value' => $referralSummary['total_referrals']],
                        ['label' => 'Active Referrals', 'value' => $referralSummary['active_referrals']],
                        ['label' => 'Conversion Rate', 'value' => $referralSummary['conversion_rate'] . '%'],
                        ['label' => 'Pending Tours', 'value' => $referralSummary['pending_tours']],
                    ];
                @endphp
                @foreach($referralKpiCards as $stat)
                    <div class="col-6 col-lg-3">
                        <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                            <div class="card-body p-4">
                                <div class="fw-bold" style="font-size:1.5rem;color:var(--hb-gray-900);line-height:1;">{{ $stat['value'] }}</div>
                                <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.25rem;">{{ $stat['label'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Referral Status Summary --}}
        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Referrals by Status</h6>
                    @forelse($referralStatusCounts as $status => $count)
                        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <span style="font-size:0.875rem;color:var(--hb-gray-900);">{{ \App\Enums\ReferralStatus::from($status)->label() }}</span>
                            <span class="fw-bold" style="color:var(--hb-emerald-700);">{{ $count }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No referrals yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Conversion Funnel --}}
        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Referral Conversion Funnel</h6>
                    @php $funnelMax = $conversionFunnel->max() ?: 1; @endphp
                    @forelse($conversionFunnel as $stageLabel => $count)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between" style="font-size:0.8rem;">
                                <span style="color:var(--hb-gray-900);">{{ $stageLabel }}</span>
                                <span style="color:var(--hb-gray-600);">{{ $count }}</span>
                            </div>
                            <div style="background:var(--hb-gray-200);border-radius:999px;height:8px;overflow:hidden;">
                                <div style="background:var(--hb-emerald-700);height:100%;width:{{ $funnelMax > 0 ? ($count / $funnelMax) * 100 : 0 }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No referrals yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Referral Activity --}}
        <div class="col-12">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Recent Referral Activity</h6>
                        <a href="{{ route('admin.referrals.index') }}" class="hb-link" style="font-size:0.8rem;">View all referrals</a>
                    </div>
                    @forelse($recentReferralActivity as $entry)
                        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.85rem;">
                            <div>
                                <a href="{{ route('admin.referrals.show', $entry->referral) }}" class="hb-link">{{ $entry->referral->agency->name }}</a>
                                <span style="color:var(--hb-gray-600);"> — {{ \App\Enums\ReferralStatus::from($entry->to_status)->label() }}</span>
                            </div>
                            <span style="color:var(--hb-gray-600);">{{ $entry->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No referral activity yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Review Analytics (Phase 14) --}}
        <div class="col-12">
            <div class="row g-3 mb-1">
                @php
                    $reviewKpiCards = [
                        ['label' => 'Published Reviews', 'value' => $reviewSummary['total_published']],
                        ['label' => 'Pending Moderation', 'value' => $reviewSummary['pending_moderation']],
                        ['label' => 'Reported', 'value' => $reviewSummary['reported']],
                        ['label' => 'Average Platform Rating', 'value' => $reviewSummary['average_platform_rating']],
                    ];
                @endphp
                @foreach($reviewKpiCards as $stat)
                    <div class="col-6 col-lg-3">
                        <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                            <div class="card-body p-4">
                                <div class="fw-bold" style="font-size:1.5rem;color:var(--hb-gray-900);line-height:1;">{{ $stat['value'] }}</div>
                                <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.25rem;">{{ $stat['label'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-trophy me-2" style="color:var(--hb-gold-500);"></i>Top Rated Agencies</h6>
                    @forelse($topRatedAgencies as $agency)
                        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.875rem;">
                            <span>{{ $agency->name }}</span>
                            <span class="fw-bold" style="color:var(--hb-emerald-700);">{{ number_format((float) $agency->review_score, 1) }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">Not enough rated agencies yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-exclamation-triangle me-2" style="color:var(--hb-warning);"></i>Lowest Rated Agencies</h6>
                    @forelse($lowestRatedAgencies as $agency)
                        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.875rem;">
                            <a href="{{ route('admin.agencies.show', $agency) }}" class="hb-link">{{ $agency->name }}</a>
                            <span class="fw-bold" style="color:var(--hb-danger);">{{ number_format((float) $agency->review_score, 1) }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">Not enough rated agencies yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Financial Analytics (Phase 16) --}}
        <div class="col-12">
            <h5 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-cash-coin me-2"></i>Financial Overview</h5>
            <div class="row g-3 mb-3">
                @php
                    $financialKpiCards = [
                        ['label' => 'Total Revenue', 'value' => '$' . number_format($financialSummary['total_revenue'], 2)],
                        ['label' => 'Revenue This Month', 'value' => '$' . number_format($financialSummary['revenue_this_month'], 2)],
                        ['label' => 'Monthly Recurring Revenue', 'value' => '$' . number_format($financialSummary['mrr'], 2)],
                        ['label' => 'Annual Recurring Revenue', 'value' => '$' . number_format($financialSummary['arr'], 2)],
                        ['label' => 'Active Subscriptions', 'value' => $financialSummary['active_subscriptions']],
                        ['label' => 'Trial Users', 'value' => $financialSummary['trial_users']],
                        ['label' => 'Churn Rate (30d)', 'value' => $financialSummary['churn_rate'] . '%'],
                        ['label' => 'Failed Payments', 'value' => $financialSummary['failed_payments_count']],
                        ['label' => 'Total Refunded', 'value' => '$' . number_format($financialSummary['total_refunded'], 2)],
                        ['label' => 'Featured Listing Revenue', 'value' => '$' . number_format($financialSummary['featured_listing_revenue'], 2)],
                    ];
                @endphp
                @foreach($financialKpiCards as $stat)
                    <div class="col-6 col-lg-3">
                        <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                            <div class="card-body p-4">
                                <div class="fw-bold" style="font-size:1.4rem;color:var(--hb-gray-900);line-height:1;">{{ $stat['value'] }}</div>
                                <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.25rem;">{{ $stat['label'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-3 mb-1">
                <div class="col-lg-6">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Refund Queue</h6>
                                <a href="{{ route('admin.billing.refunds.index') }}" class="hb-link" style="font-size:0.8rem;">View All ({{ $financialSummary['pending_refunds_count'] }})</a>
                            </div>
                            @forelse($pendingRefunds as $refund)
                                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.85rem;">
                                    <div>
                                        <div class="fw-bold">{{ $refund->payment->invoice->agency->name ?? 'N/A' }}</div>
                                        <div style="color:var(--hb-gray-600);font-size:0.75rem;">{{ $refund->reason }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">${{ number_format((float) $refund->amount, 2) }}</div>
                                        <div style="color:var(--hb-gray-600);font-size:0.75rem;">{{ $refund->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @empty
                                <p style="font-size:0.85rem;color:var(--hb-gray-600);">No pending refund requests.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Recent Transactions</h6>
                            @forelse($recentTransactions as $payment)
                                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.85rem;">
                                    <div>
                                        <div class="fw-bold">{{ $payment->invoice->agency->name ?? 'N/A' }}</div>
                                        <div style="color:var(--hb-gray-600);font-size:0.75rem;">{{ $payment->invoice->invoice_number ?? '' }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">${{ number_format((float) $payment->amount, 2) }}</div>
                                        <span class="hb-badge-verified" style="font-size:0.7rem;">{{ $payment->status->label() }}</span>
                                    </div>
                                </div>
                            @empty
                                <p style="font-size:0.85rem;color:var(--hb-gray-600);">No transactions yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-1">
                <div class="col-lg-6">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Top-Selling Plans</h6>
                            @foreach($topSellingPlans as $plan)
                                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.85rem;">
                                    <span>{{ $plan->name }}</span>
                                    <span class="fw-bold">{{ $plan->subscriptions_count }} active</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Recent Failed Payments</h6>
                            @forelse($recentFailedPayments as $payment)
                                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.85rem;">
                                    <div>
                                        <div class="fw-bold">{{ $payment->invoice->agency->name ?? 'N/A' }}</div>
                                        <div style="color:#991B1B;font-size:0.75rem;">{{ $payment->failure_reason }}</div>
                                    </div>
                                    <div class="fw-bold">${{ number_format((float) $payment->amount, 2) }}</div>
                                </div>
                            @empty
                                <p style="font-size:0.85rem;color:var(--hb-gray-600);">No failed payments.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CMS Analytics (Phase 15) --}}
        <div class="col-12">
            <div class="row g-3 mb-1">
                @php
                    $cmsKpiCards = [
                        ['label' => 'Published Pages', 'value' => $cmsSummary['published_pages']],
                        ['label' => 'Draft Pages', 'value' => $cmsSummary['draft_pages']],
                        ['label' => 'Published Posts', 'value' => $cmsSummary['published_posts']],
                        ['label' => 'Scheduled Posts', 'value' => $cmsSummary['scheduled_posts']],
                    ];
                @endphp
                @foreach($cmsKpiCards as $stat)
                    <div class="col-6 col-lg-3">
                        <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                            <div class="card-body p-4">
                                <div class="fw-bold" style="font-size:1.5rem;color:var(--hb-gray-900);line-height:1;">{{ $stat['value'] }}</div>
                                <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.25rem;">{{ $stat['label'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Leads Trend --}}
        <div class="col-12">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Leads Created — Last 30 Days</h6>
                    @if($leadsPerDay->isEmpty())
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No leads in this period.</p>
                    @else
                        <div class="d-flex align-items-end gap-1" style="height:120px;">
                            @php $maxCount = $leadsPerDay->max('count') ?: 1; @endphp
                            @foreach($leadsPerDay as $day)
                                <div style="flex:1;background:var(--hb-emerald-700);border-radius:0.25rem 0.25rem 0 0;height:{{ max(4, ($day->count / $maxCount) * 100) }}%;"
                                     title="{{ $day->date }}: {{ $day->count }}"></div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

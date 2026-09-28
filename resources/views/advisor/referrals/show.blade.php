<x-advisor-layout title="Referral — {{ $referral->agency->name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('advisor.referrals.index') }}" class="hb-link">Referrals</a></li>
        <li class="breadcrumb-item active">{{ $referral->agency->name }}</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i>
            @switch(session('status'))
                @case('referral-updated') Referral updated. @break
                @case('referral-cancelled') Referral cancelled. @break
                @case('note-added') Note added. @break
                @case('tour-scheduled') Tour scheduled. @break
                @default Done. @endswitch
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Header card with status + actions --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color:var(--hb-gray-900);">{{ $referral->agency->name }}</h5>
                            <div style="font-size:0.85rem;color:var(--hb-gray-600);">
                                For {{ $referral->family_name }} &middot; {{ $referral->careSeeker?->full_name }}
                            </div>
                        </div>
                        @php $c = $referral->status->badgeColor(); @endphp
                        <span style="font-size:0.8rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.35rem 0.85rem;border-radius:999px;">
                            {{ $referral->status->label() }}
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-3" style="font-size:0.8rem;color:var(--hb-gray-600);">
                        <span><i class="bi bi-calendar me-1"></i>Sent {{ $referral->sent_at?->format('M d, Y') ?? 'not yet' }}</span>
                        <form method="POST" action="{{ route('advisor.referrals.priority', $referral) }}" class="d-flex align-items-center gap-1">
                            @csrf @method('PUT')
                            <label style="font-size:0.8rem;">Priority:</label>
                            <select name="priority" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                @foreach(\App\Enums\ReferralPriority::cases() as $p)
                                    <option value="{{ $p->value }}" {{ $referral->priority === $p ? 'selected' : '' }}>{{ $p->label() }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    {{-- Pipeline actions: at most ONE forward progression
                         button, plus Cancel/Close-as-Lost as separate,
                         distinctly-styled escape hatches — same pattern
                         fixed on the Lead Pipeline (Phase 12), applied
                         here from the start. --}}
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @foreach($referral->status->allowedNextStatuses() as $next)
                            @if($next === \App\Enums\ReferralStatus::TourScheduled)
                                {{-- Handled via the dedicated Schedule Tour
                                     form below, not a plain status button,
                                     since it needs a date. --}}
                            @else
                                <form method="POST" action="{{ route('advisor.referrals.transition', $referral) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="status" value="{{ $next->value }}">
                                    <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.625rem;">
                                        <i class="bi bi-arrow-right-circle me-1"></i>Move to {{ $next->label() }}
                                    </button>
                                </form>
                            @endif
                        @endforeach

                        @if($referral->status->canCloseAsLost())
                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#closeLostModal" style="border-radius:0.625rem;">
                                <i class="bi bi-x-circle me-1"></i>Close as Lost
                            </button>
                        @endif
                        @if($referral->status->canCancel())
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal" style="border-radius:0.625rem;">
                                <i class="bi bi-slash-circle me-1"></i>Cancel Referral
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Schedule tour — only meaningful once the agency has accepted --}}
            @if($referral->status === \App\Enums\ReferralStatus::AgencyAccepted)
                <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-calendar-plus me-2"></i>Schedule Tour</h6>
                        <form method="POST" action="{{ route('advisor.referrals.tours.store', $referral) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="hb-form-label">Date</label>
                                <input type="date" name="requested_date" class="hb-form-control" required min="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-4">
                                <label class="hb-form-label">Time Window</label>
                                <input type="text" name="requested_time_window" class="hb-form-control" placeholder="e.g. 2-4 PM">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">Schedule</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Scheduled tours for this referral --}}
            @if($referral->tourRequests->isNotEmpty())
                <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Tours</h6>
                        @foreach($referral->tourRequests as $tour)
                            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.875rem;">
                                <span>{{ $tour->requested_date->format('M d, Y') }} @if($tour->requested_time_window) &middot; {{ $tour->requested_time_window }} @endif</span>
                                <span class="hb-badge-verified">{{ $tour->status->label() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Notes --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Notes</h6>
                    @forelse($visibleNotes as $note)
                        <div class="mb-3 pb-3" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div class="d-flex justify-content-between" style="font-size:0.75rem;color:var(--hb-gray-600);">
                                <span>
                                    <span class="hb-badge-verified" style="font-size:0.65rem;">{{ ucfirst($note->author_type) }}</span>
                                    {{ $note->author?->name }}
                                </span>
                                <span>{{ $note->created_at->diffForHumans() }}</span>
                            </div>
                            <div style="font-size:0.875rem;color:var(--hb-gray-900);margin-top:0.25rem;">{{ $note->content }}</div>
                            <div style="font-size:0.7rem;color:var(--hb-gray-600);margin-top:0.25rem;">
                                @if($note->visible_to_agency)<i class="bi bi-eye me-1"></i>Visible to agency @endif
                                @if($note->visible_to_family)<i class="bi bi-eye me-1"></i>Visible to family @endif
                            </div>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);">No notes yet.</p>
                    @endforelse

                    <form method="POST" action="{{ route('advisor.referrals.notes.store', $referral) }}" class="mt-3">
                        @csrf
                        <textarea name="content" class="hb-form-control mb-2" rows="2" placeholder="Add a note..." required></textarea>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="visible_to_agency" value="1" id="noteVisAgency">
                                <label class="form-check-label" for="noteVisAgency" style="font-size:0.8rem;">Share with agency</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="visible_to_family" value="1" id="noteVisFamily">
                                <label class="form-check-label" for="noteVisFamily" style="font-size:0.8rem;">Share with family</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.625rem;">Add Note</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Timeline / Audit History --}}
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-clock-history me-2"></i>Timeline</h6>
                    @forelse($referral->statusHistory as $entry)
                        <div class="mb-3 pb-3" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.8rem;">
                            <div style="font-weight:600;color:var(--hb-gray-900);">
                                {{ \App\Enums\ReferralStatus::from($entry->to_status)->label() }}
                            </div>
                            <div style="color:var(--hb-gray-600);">{{ $entry->changedBy?->name ?? 'System' }} &middot; {{ $entry->created_at->diffForHumans() }}</div>
                            @if($entry->reason)
                                <div style="color:var(--hb-gray-600);font-style:italic;margin-top:0.2rem;">"{{ $entry->reason }}"</div>
                            @endif
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);">No history yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Close as Lost modal --}}
    <div class="modal fade" id="closeLostModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="PUT" action="{{ route('advisor.referrals.transition', $referral) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="status" value="closed_lost">
                    <div class="modal-header"><h6 class="modal-title">Close Referral as Lost</h6></div>
                    <div class="modal-body">
                        <label class="hb-form-label">Reason (required)</label>
                        <textarea name="reason" class="hb-form-control" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Close as Lost</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Cancel modal --}}
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('advisor.referrals.cancel', $referral) }}">
                    @csrf
                    <div class="modal-header"><h6 class="modal-title">Cancel Referral</h6></div>
                    <div class="modal-body">
                        <label class="hb-form-label">Reason (required)</label>
                        <textarea name="reason" class="hb-form-control" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Never Mind</button>
                        <button type="submit" class="btn btn-danger">Cancel Referral</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-advisor-layout>

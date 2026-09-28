<x-agency-layout title="Referral — {{ $referral->careSeeker?->full_name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('agency.referrals.index') }}" class="hb-link">Referral Inbox</a></li>
        <li class="breadcrumb-item active">{{ $referral->careSeeker?->full_name }}</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000">
            <i class="bi bi-check-circle me-2"></i>Updated successfully.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold mb-1" style="color:var(--hb-gray-900);">{{ $referral->careSeeker?->full_name }}</h5>
                            <div style="font-size:0.85rem;color:var(--hb-gray-600);">
                                Referred by {{ $referral->advisor?->user?->name }}
                            </div>
                        </div>
                        @php $c = $referral->status->badgeColor(); @endphp
                        <span style="font-size:0.8rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.35rem 0.85rem;border-radius:999px;">
                            {{ $referral->status->label() }}
                        </span>
                    </div>

                    {{-- Family contact info — gated until acceptance, per
                         "View Family Contact Information only after
                         referral is accepted." Enforced server-side in
                         the controller (the data isn't even fetched into
                         $showFamilyContact-false responses' relevant
                         fields being displayed here), not just hidden by
                         CSS. --}}
                    @if($showFamilyContact)
                        <div class="mb-3 p-3" style="background:var(--hb-gray-50);border-radius:0.625rem;">
                            <div style="font-size:0.7rem;font-weight:700;color:var(--hb-gray-600);text-transform:uppercase;margin-bottom:0.35rem;">Family Contact</div>
                            <div style="font-size:0.875rem;"><i class="bi bi-person me-1"></i>{{ $referral->family->user->name }}</div>
                            <div style="font-size:0.875rem;"><i class="bi bi-envelope me-1"></i>{{ $referral->family->user->email }}</div>
                            @if($referral->family->phone)
                                <div style="font-size:0.875rem;"><i class="bi bi-telephone me-1"></i>{{ $referral->family->phone }}</div>
                            @endif
                        </div>
                    @else
                        <div class="mb-3 p-3" style="background:var(--hb-gray-50);border-radius:0.625rem;font-size:0.8rem;color:var(--hb-gray-600);">
                            <i class="bi bi-lock me-1"></i>Family contact information will be available once you accept this referral.
                        </div>
                    @endif

                    @if($referral->status === \App\Enums\ReferralStatus::SentToAgency)
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('agency.referrals.accept', $referral) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary" style="border-radius:0.625rem;">
                                    <i class="bi bi-check-circle me-1"></i>Accept Referral
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#declineModal" style="border-radius:0.625rem;">
                                <i class="bi bi-x-circle me-1"></i>Decline
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#requestInfoModal" style="border-radius:0.625rem;">
                                <i class="bi bi-question-circle me-1"></i>Request More Information
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Schedule tour once accepted --}}
            @if($referral->status === \App\Enums\ReferralStatus::AgencyAccepted)
                <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-calendar-plus me-2"></i>Schedule Tour</h6>
                        <form method="POST" action="{{ route('agency.referrals.tours.store', $referral) }}" class="row g-2 align-items-end">
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

            {{-- Tours + complete action --}}
            @if($referral->tourRequests->isNotEmpty())
                <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Tours</h6>
                        @foreach($referral->tourRequests as $tour)
                            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.875rem;">
                                <span>{{ $tour->requested_date->format('M d, Y') }} @if($tour->requested_time_window) &middot; {{ $tour->requested_time_window }} @endif</span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="hb-badge-verified">{{ $tour->status->label() }}</span>
                                    @if(in_array($tour->status->value, ['requested', 'confirmed']))
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#rescheduleModal{{ $tour->id }}" style="border-radius:0.5rem;">Reschedule</button>
                                    @endif
                                    @if($tour->status->value === 'requested')
                                        <form method="POST" action="{{ route('agency.tours.confirm', $tour) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius:0.5rem;">Confirm</button>
                                        </form>
                                    @endif
                                    @if(in_array($tour->status->value, ['requested', 'confirmed']))
                                        <form method="POST" action="{{ route('agency.tours.complete', $tour) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" style="border-radius:0.5rem;">Mark Completed</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="modal fade" id="rescheduleModal{{ $tour->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="PUT" action="{{ route('agency.tours.reschedule', $tour) }}">
                                            @csrf @method('PUT')
                                            <div class="modal-header"><h6 class="modal-title">Reschedule Tour</h6></div>
                                            <div class="modal-body">
                                                <label class="hb-form-label">New Date</label>
                                                <input type="date" name="requested_date" class="hb-form-control mb-2" required min="{{ now()->toDateString() }}" value="{{ $tour->requested_date->toDateString() }}">
                                                <label class="hb-form-label">Time Window</label>
                                                <input type="text" name="requested_time_window" class="hb-form-control" value="{{ $tour->requested_time_window }}">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Reschedule</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
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
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);">No notes shared yet.</p>
                    @endforelse

                    <form method="POST" action="{{ route('agency.referrals.notes.store', $referral) }}" class="mt-3">
                        @csrf
                        <textarea name="content" class="hb-form-control mb-2" rows="2" placeholder="Add a note..." required></textarea>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" name="visible_to_family" value="1" id="agencyNoteVisFamily">
                            <label class="form-check-label" for="agencyNoteVisFamily" style="font-size:0.8rem;">Share with family</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.625rem;">Add Note</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);"><i class="bi bi-clock-history me-2"></i>Timeline</h6>
                    @forelse($referral->statusHistory as $entry)
                        <div class="mb-3 pb-3" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.8rem;">
                            <div style="font-weight:600;color:var(--hb-gray-900);">
                                {{ \App\Enums\ReferralStatus::from($entry->to_status)->label() }}
                            </div>
                            <div style="color:var(--hb-gray-600);">{{ $entry->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);">No history yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="declineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('agency.referrals.decline', $referral) }}">
                    @csrf
                    <div class="modal-header"><h6 class="modal-title">Decline Referral</h6></div>
                    <div class="modal-body">
                        <label class="hb-form-label">Reason (required)</label>
                        <textarea name="reason" class="hb-form-control" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Never Mind</button>
                        <button type="submit" class="btn btn-danger">Decline</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="requestInfoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('agency.referrals.request-info', $referral) }}">
                    @csrf
                    <div class="modal-header"><h6 class="modal-title">Request More Information</h6></div>
                    <div class="modal-body">
                        <label class="hb-form-label">Your question</label>
                        <textarea name="content" class="hb-form-control" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-agency-layout>

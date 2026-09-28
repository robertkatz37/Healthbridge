<x-admin-layout title="{{ $agency->name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.agencies.index') }}" class="hb-link">Agencies</a></li>
        <li class="breadcrumb-item active">{{ $agency->name }}</li>
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

    {{-- Header --}}
    <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h5 class="fw-bold mb-0" style="color:var(--hb-gray-900);">{{ $agency->name }}</h5>
                        <span style="font-size:0.75rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.2rem 0.6rem;border-radius:999px;">
                            {{ $agency->status->label() }}
                        </span>
                    </div>
                    <div style="font-size:0.85rem;color:var(--hb-gray-600);">
                        {{ $agency->category?->name }} &middot; {{ $agency->city }}, {{ $agency->state }}
                    </div>
                    <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.375rem;">
                        <i class="bi bi-person me-1"></i>Owner: {{ $agency->user->name }} ({{ $agency->user->email }})
                    </div>
                </div>

                {{-- Moderation Actions --}}
                <div class="d-flex gap-2 flex-wrap">
                    @can('approve', $agency)
                        @if(in_array(\App\Enums\AgencyStatus::Approved, $agency->status->allowedNextStatuses()))
                            <form method="POST" action="{{ route('admin.agencies.approve', $agency) }}"
                                  onsubmit="return confirm('Approve and publish this agency?')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" style="border-radius:0.625rem;">
                                    <i class="bi bi-check-lg me-1"></i>Approve
                                </button>
                            </form>
                        @endif
                    @endcan

                    @if(in_array(\App\Enums\AgencyStatus::ChangesRequested, $agency->status->allowedNextStatuses()))
                        <button type="button" class="btn btn-warning btn-sm" style="border-radius:0.625rem;"
                                data-bs-toggle="modal" data-bs-target="#requestChangesModal">
                            <i class="bi bi-pencil-square me-1"></i>Request Changes
                        </button>
                    @endif

                    @if(in_array(\App\Enums\AgencyStatus::Rejected, $agency->status->allowedNextStatuses()))
                        <button type="button" class="btn btn-outline-danger btn-sm" style="border-radius:0.625rem;"
                                data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-lg me-1"></i>Reject
                        </button>
                    @endif

                    @if($agency->status->value === 'published')
                        <button type="button" class="btn btn-outline-danger btn-sm" style="border-radius:0.625rem;"
                                data-bs-toggle="modal" data-bs-target="#suspendModal">
                            <i class="bi bi-slash-circle me-1"></i>Suspend
                        </button>
                    @endif

                    @if($agency->status->value === 'suspended')
                        <form method="POST" action="{{ route('admin.agencies.reactivate', $agency) }}"
                              onsubmit="return confirm('Reactivate this agency? It will become publicly visible again.')">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm" style="border-radius:0.625rem;">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reactivate
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">

            {{-- Business Info --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Business Information</h6>
                    <p style="font-size:0.875rem;color:var(--hb-gray-600);">{{ $agency->description ?: 'No description provided.' }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">Phone</div>
                            <div style="font-size:0.875rem;font-weight:600;">{{ $agency->phone ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">Email</div>
                            <div style="font-size:0.875rem;font-weight:600;">{{ $agency->email ?: '—' }}</div>
                        </div>
                        <div class="col-md-12">
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">Address</div>
                            <div style="font-size:0.875rem;font-weight:600;">{{ $agency->address }}, {{ $agency->city }}, {{ $agency->state }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Services --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">
                        Services <span style="font-weight:400;color:var(--hb-gray-600);font-size:0.8rem;">({{ $agency->services->count() }})</span>
                    </h6>
                    @forelse($agency->services as $service)
                        <div class="d-flex justify-content-between py-1" style="font-size:0.875rem;">
                            <span>{{ $service->name }}</span>
                            <span style="color:var(--hb-gray-600);">{{ $service->price_from ? '$'.number_format($service->price_from,0).'/mo' : '' }}</span>
                        </div>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No services listed.</p>
                    @endforelse
                </div>
            </div>

            {{-- Coverage & Hours --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Coverage Areas</h6>
                    @forelse($agency->coverage as $area)
                        <span class="hb-badge-verified me-1 mb-1 d-inline-block">{{ $area->city }}, {{ $area->state }}</span>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No coverage areas listed.</p>
                    @endforelse
                </div>
            </div>

            {{-- Certifications --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">
                        Certifications <span style="font-weight:400;color:var(--hb-gray-600);font-size:0.8rem;">({{ $agency->certifications->count() }})</span>
                    </h6>
                    @forelse($agency->certifications as $cert)
                        <div class="d-flex align-items-center gap-2 py-1" style="font-size:0.875rem;">
                            <i class="bi bi-patch-check-fill" style="color:var(--hb-emerald-700);"></i>
                            {{ $cert->name }}
                            @if($cert->expires_at)
                                <span style="font-size:0.775rem;color:var(--hb-gray-600);">(expires {{ $cert->expires_at->format('M Y') }})</span>
                            @endif
                        </div>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No certifications listed.</p>
                    @endforelse
                </div>
            </div>

            {{-- Documents (private, admin download) --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">
                        <i class="bi bi-shield-lock me-2" style="color:var(--hb-gray-600);"></i>Uploaded Documents
                        <span style="font-weight:400;color:var(--hb-gray-600);font-size:0.8rem;">({{ $agency->documents->count() }})</span>
                    </h6>
                    @forelse($agency->documents as $doc)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.875rem;">
                            <div>
                                <i class="bi bi-file-earmark-text me-1" style="color:var(--hb-gray-600);"></i>
                                {{ $doc->original_filename }}
                                <span style="font-size:0.75rem;color:var(--hb-gray-600);">({{ ucfirst($doc->document_type->value) }})</span>
                            </div>
                            <a href="{{ route('admin.agencies.documents.download', [$agency, $doc]) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No documents uploaded.</p>
                    @endforelse
                </div>
            </div>

            {{-- Status History Timeline --}}
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Status History</h6>
                    @forelse($agency->statusHistory as $entry)
                        <div class="d-flex gap-3 py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div style="width:32px;height:32px;background:var(--hb-emerald-100);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-arrow-right" style="font-size:0.8rem;color:var(--hb-emerald-700);"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">
                                    {{ $entry->from_status ? ucfirst(str_replace('_',' ',$entry->from_status)) . ' → ' : '' }}{{ ucfirst(str_replace('_',' ',$entry->to_status)) }}
                                </div>
                                @if($entry->reason)
                                    <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.2rem;">{{ $entry->reason }}</div>
                                @endif
                                <div style="font-size:0.75rem;color:var(--hb-gray-600);margin-top:0.2rem;">
                                    {{ $entry->changedBy?->name ?? 'System' }} &middot; {{ $entry->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No status changes recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar: Internal Notes --}}
        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);position:sticky;top:1rem;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1" style="color:var(--hb-gray-900);">
                        <i class="bi bi-journal-lock me-2" style="color:var(--hb-gray-600);"></i>Internal Notes
                    </h6>
                    <p style="font-size:0.75rem;color:var(--hb-gray-600);margin-bottom:1rem;">
                        Visible only to platform staff — never shown to the agency owner.
                    </p>

                    <form method="POST" action="{{ route('admin.agencies.notes.store', $agency) }}" class="mb-3">
                        @csrf
                        <textarea name="note" rows="3" class="hb-form-control @error('note') is-invalid @enderror"
                                  placeholder="Add an internal note..."></textarea>
                        @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <button type="submit" class="btn btn-sm btn-outline-primary mt-2" style="border-radius:0.625rem;">
                            <i class="bi bi-plus-lg me-1"></i>Add Note
                        </button>
                    </form>

                    <hr style="border-color:var(--hb-gray-200);">

                    @forelse($agency->adminNotes as $note)
                        <div class="mb-3 pb-3" style="border-bottom:1px solid var(--hb-gray-200);">
                            <p style="font-size:0.85rem;color:var(--hb-gray-900);margin-bottom:0.375rem;">{{ $note->note }}</p>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">
                                {{ $note->author->name }} &middot; {{ $note->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);">No internal notes yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Request Changes Modal --}}
    <div class="modal fade" id="requestChangesModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold">Request Changes</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('admin.agencies.request-changes', $agency) }}">
                    @csrf
                    <div class="modal-body">
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">
                            Explain what the owner needs to fix. This will be emailed to them.
                        </p>
                        <textarea name="notes" rows="4" class="hb-form-control" placeholder="e.g. Please upload a current liability insurance certificate." required minlength="10"></textarea>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-warning" style="border-radius:0.75rem;">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold text-danger">Reject Application</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('admin.agencies.reject', $agency) }}">
                    @csrf
                    <div class="modal-body">
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">
                            This will be emailed to the owner. This action is difficult to reverse.
                        </p>
                        <textarea name="reason" rows="4" class="hb-form-control" placeholder="Reason for rejection..." required minlength="10"></textarea>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-danger" style="border-radius:0.75rem;">Reject Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Suspend Modal --}}
    <div class="modal fade" id="suspendModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold text-danger">Suspend Agency</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('admin.agencies.suspend', $agency) }}">
                    @csrf
                    <div class="modal-body">
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">
                            This immediately removes the listing from public view. The owner will be notified.
                        </p>
                        <textarea name="reason" rows="4" class="hb-form-control" placeholder="Reason for suspension..." required minlength="10"></textarea>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-danger" style="border-radius:0.75rem;">Suspend Agency</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>

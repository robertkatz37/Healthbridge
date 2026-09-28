<x-advisor-layout title="Lead Pipeline">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Pipeline</li>
    @endslot

    @php
        $statusMeta = [
            'new' => ['label' => 'New', 'bg' => '#F3F4F6', 'text' => '#4B5563', 'bar' => '#9CA3AF'],
            'assigned' => ['label' => 'Assigned', 'bg' => '#EFF6FF', 'text' => '#1E40AF', 'bar' => '#3B82F6'],
            'contacted' => ['label' => 'Contacted', 'bg' => '#EFF6FF', 'text' => '#1E40AF', 'bar' => '#60A5FA'],
            'assessment_reviewed' => ['label' => 'Assessment Reviewed', 'bg' => '#FFFBEB', 'text' => '#92400E', 'bar' => '#F59E0B'],
            'shortlisted' => ['label' => 'Shortlisted', 'bg' => '#FFFBEB', 'text' => '#92400E', 'bar' => '#FBBF24'],
            'tour_scheduled' => ['label' => 'Tour Scheduled', 'bg' => '#ECFDF5', 'text' => '#065F46', 'bar' => '#10B981'],
            'follow_up' => ['label' => 'Follow-up', 'bg' => '#FFFBEB', 'text' => '#92400E', 'bar' => '#D97706'],
            'move_in_confirmed' => ['label' => 'Move-In Confirmed', 'bg' => '#ECFDF5', 'text' => '#065F46', 'bar' => '#059669'],
            'converted' => ['label' => 'Converted', 'bg' => '#ECFDF5', 'text' => '#065F46', 'bar' => '#047857'],
            'closed_lost' => ['label' => 'Closed Lost', 'bg' => '#FEF2F2', 'text' => '#991B1B', 'bar' => '#DC2626'],
        ];
    @endphp

    {{-- Pipeline Statistics --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Total Leads', 'value' => $stats['total'], 'color' => 'var(--hb-emerald-700)'],
            ['label' => 'Open', 'value' => $stats['open'], 'color' => 'var(--hb-warning)'],
            ['label' => 'Converted', 'value' => $stats['converted'], 'color' => 'var(--hb-success)'],
            ['label' => 'Closed Lost', 'value' => $stats['closed_lost'], 'color' => 'var(--hb-danger)'],
        ] as $stat)
            <div class="col-6 col-md-3">
                <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                    <div class="card-body p-3">
                        <div class="fw-bold" style="font-size:1.4rem;color:{{ $stat['color'] }};">{{ $stat['value'] }}</div>
                        <div style="font-size:0.775rem;color:var(--hb-gray-600);">{{ $stat['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div id="pipelineError" class="hb-alert hb-alert-danger mb-3" style="display:none;"></div>

    {{-- Kanban Board --}}
    <div style="overflow-x:auto;padding-bottom:1rem;">
        <div class="d-flex gap-3" style="min-width:max-content;">
            @foreach($columns as $statusValue => $leads)
                @php $meta = $statusMeta[$statusValue]; @endphp
                <div class="pipeline-column" style="width:270px;flex-shrink:0;">
                    <div style="background:{{ $meta['bg'] }};border-radius:0.75rem 0.75rem 0 0;padding:0.75rem 1rem;border-top:3px solid {{ $meta['bar'] }};">
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-size:0.8rem;font-weight:700;color:{{ $meta['text'] }};">{{ $meta['label'] }}</span>
                            <span style="font-size:0.75rem;font-weight:700;background:white;color:{{ $meta['text'] }};padding:0.1rem 0.5rem;border-radius:999px;">{{ $leads->count() }}</span>
                        </div>
                    </div>
                    <div class="pipeline-drop-zone" data-status="{{ $statusValue }}"
                         style="background:var(--hb-gray-50);border-radius:0 0 0.75rem 0.75rem;padding:0.5rem;min-height:120px;max-height:65vh;overflow-y:auto;">
                        @forelse($leads as $lead)
                            <div class="pipeline-card" draggable="true" data-lead-id="{{ $lead->id }}" data-status="{{ $statusValue }}"
                                 style="background:white;border-radius:0.625rem;padding:0.75rem;margin-bottom:0.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.08);cursor:grab;">
                                <a href="{{ route('advisor.leads.show', $lead) }}" class="text-decoration-none">
                                    <div style="font-size:0.85rem;font-weight:600;color:var(--hb-gray-900);">{{ $lead->family_name }}</div>
                                </a>
                                @if($lead->careSeeker)
                                    <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $lead->careSeeker->full_name }}</div>
                                @endif
                                <div style="font-size:0.7rem;color:var(--hb-gray-600);margin-top:0.35rem;">
                                    <i class="bi bi-clock me-1"></i>{{ $lead->created_at->diffForHumans() }}
                                </div>
                                @if($lead->advisor && auth()->id() !== $lead->advisor->user_id)
                                    <div style="font-size:0.7rem;color:var(--hb-gray-600);margin-top:0.15rem;">
                                        <i class="bi bi-person me-1"></i>{{ $lead->advisor->user->name }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);text-align:center;padding:1rem 0;">No leads</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="modal fade" id="closedLostReasonModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold text-danger">Close Lead — Lost</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="hb-form-label">Reason</label>
                    <textarea id="closedLostReasonInput" rows="3" class="hb-form-control" minlength="5"></textarea>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmClosedLost" style="border-radius:0.75rem;">Close Lead</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let draggedCard = null;
        let pendingClosedLostMove = null;

        document.querySelectorAll('.pipeline-card').forEach(card => {
            card.addEventListener('dragstart', () => { draggedCard = card; card.style.opacity = '0.4'; });
            card.addEventListener('dragend', () => { card.style.opacity = '1'; });
        });

        document.querySelectorAll('.pipeline-drop-zone').forEach(zone => {
            zone.addEventListener('dragover', (e) => e.preventDefault());
            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                if (!draggedCard) return;
                const targetStatus = zone.dataset.status;
                const leadId = draggedCard.dataset.leadId;
                const currentStatus = draggedCard.dataset.status;
                if (targetStatus === currentStatus) return;

                if (targetStatus === 'closed_lost') {
                    pendingClosedLostMove = { leadId, zone, card: draggedCard };
                    const modal = new bootstrap.Modal(document.getElementById('closedLostReasonModal'));
                    modal.show();
                    return;
                }

                moveLead(leadId, targetStatus, zone, draggedCard);
            });
        });

        document.getElementById('confirmClosedLost').addEventListener('click', () => {
            const reason = document.getElementById('closedLostReasonInput').value;
            if (reason.trim().length < 5) { return; }
            const { leadId, zone, card } = pendingClosedLostMove;
            moveLead(leadId, 'closed_lost', zone, card, reason);
            bootstrap.Modal.getInstance(document.getElementById('closedLostReasonModal')).hide();
        });

        function moveLead(leadId, status, zone, card, reason = null) {
            fetch(`/advisor/leads/${leadId}/move`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status, reason }),
            })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Move failed');
                zone.appendChild(card);
                card.dataset.status = status;
                document.getElementById('pipelineError').style.display = 'none';
            })
            .catch(err => {
                const errorBox = document.getElementById('pipelineError');
                errorBox.textContent = err.message;
                errorBox.style.display = 'block';
            });
        }
    </script>
    @endpush
</x-advisor-layout>

<x-advisor-layout title="Recommendations — {{ $lead->family_name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('advisor.leads.show', $lead) }}" class="hb-link">{{ $lead->family_name }}</a></li>
        <li class="breadcrumb-item active">Recommendations</li>
    @endslot

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Agency Recommendations</h6>
            <span style="font-size:0.8rem;color:var(--hb-gray-600);">{{ $results->count() }} results — click Approve to build the family's shortlist, drag cards to reorder it</span>
        </div>
        <form method="POST" action="{{ route('advisor.leads.recommendations.generate', $lead) }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.625rem;">
                <i class="bi bi-stars me-1"></i>{{ $results->isEmpty() ? 'Generate Recommendations' : 'Regenerate Recommendations' }}
            </button>
        </form>
    </div>

    <div id="advisorMatchError" class="hb-alert hb-alert-danger mb-3" style="display:none;"></div>

    {{-- Family Shortlist — the advisor's curated, approved subset. This
         is also where referrals actually get sent to agencies: check
         one or more, set a priority, and send. --}}
    <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);border-left:4px solid var(--hb-emerald-700) !important;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-1" style="color:var(--hb-gray-900);">
                <i class="bi bi-star-fill me-2" style="color:var(--hb-emerald-700);"></i>Family Shortlist ({{ $shortlist->count() }})
            </h6>
            <p style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:0.75rem;">
                Agencies you've approved below, in the order the family will see them. Select one or more and send a referral to notify the agency.
            </p>
            @if($shortlist->isEmpty())
                <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">
                    No agencies approved yet — click <strong>Approve</strong> on a recommendation below to add it here.
                </p>
            @else
                <form method="POST" action="{{ route('advisor.leads.referrals.store', $lead) }}" id="sendReferralForm">
                    @csrf
                    <ul class="list-unstyled mb-3" style="font-size:0.875rem;">
                        @foreach($shortlist as $shortlisted)
                            @php
                                $existingReferral = $referralsByAgency->get($shortlisted->agency_id)?->first();
                                $hasOpenReferral = $openReferralAgencyIds->contains($shortlisted->agency_id);
                            @endphp
                            <li class="d-flex align-items-center gap-2 py-1">
                                @if($hasOpenReferral)
                                    <i class="bi bi-check2-square" style="color:var(--hb-gray-400);"></i>
                                @else
                                    <input type="checkbox" class="form-check-input" name="agency_ids[]" value="{{ $shortlisted->agency_id }}" id="send_{{ $shortlisted->agency_id }}">
                                @endif
                                <label for="send_{{ $shortlisted->agency_id }}" style="color:var(--hb-gray-900);flex:1;">
                                    {{ $shortlisted->agency->name }}
                                    <span style="color:var(--hb-gray-600);">— {{ round($shortlisted->compatibility_score) }}% match</span>
                                </label>
                                @if($existingReferral)
                                    @php $c = $existingReferral->status->badgeColor(); @endphp
                                    <a href="{{ route('advisor.referrals.show', $existingReferral) }}" style="font-size:0.7rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.2rem 0.6rem;border-radius:999px;text-decoration:none;">
                                        {{ $existingReferral->status->label() }}
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if($openReferralAgencyIds->count() < $shortlist->count())
                        <div class="row g-2 align-items-end">
                            <div class="col-auto">
                                <label class="hb-form-label">Priority</label>
                                <select name="priority" class="hb-form-control">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col">
                                <label class="hb-form-label">Note to agency (optional)</label>
                                <input type="text" name="notes" class="hb-form-control" placeholder="Anything the agency should know upfront...">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" style="border-radius:0.625rem;" id="sendReferralButton" disabled>
                                    <i class="bi bi-send me-1"></i>Send Referral
                                </button>
                            </div>
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('#sendReferralForm input[type=checkbox]').forEach(function (el) {
            el.addEventListener('change', function () {
                const anyChecked = document.querySelectorAll('#sendReferralForm input[type=checkbox]:checked').length > 0;
                const btn = document.getElementById('sendReferralButton');
                if (btn) btn.disabled = !anyChecked;
            });
        });
    </script>
    @endpush

    @if($results->isEmpty())
        <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body text-center py-5">
                <i class="bi bi-search" style="font-size:2.5rem;color:var(--hb-gray-200);"></i>
                <p class="mt-2 mb-0" style="color:var(--hb-gray-600);">No recommendations yet.</p>
            </div>
        </div>
    @else
        <div id="matchList">
            @foreach($results as $result)
                @php
                    $agency = $result->agency;
                    $explanation = $explanations[$result->id];
                    $tierColors = ['Excellent Match' => '#047857', 'Strong Match' => '#059669', 'Good Match' => '#D97706', 'Fair Match' => '#DC2626', 'Limited Match' => '#6B7280'];
                    $tierColor = $tierColors[$explanation['tier']] ?? '#6B7280';
                @endphp
                <div class="card mb-3 match-card" draggable="true" data-id="{{ $result->id }}"
                     style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);{{ $result->is_hidden_by_advisor ? 'opacity:0.5;' : '' }}cursor:grab;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div style="flex:1;min-width:250px;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi bi-grip-vertical" style="color:var(--hb-gray-600);"></i>
                                    <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">{{ $agency->name }}</h6>
                                    <span style="font-size:1.1rem;font-weight:800;color:{{ $tierColor }};">{{ round($result->compatibility_score) }}%</span>
                                    <span style="font-size:0.7rem;font-weight:600;color:{{ $tierColor }};">{{ $explanation['tier'] }}</span>
                                    @if($result->is_hidden_by_advisor)
                                        <span style="font-size:0.7rem;font-weight:600;background:var(--hb-gray-200);color:var(--hb-gray-600);padding:0.15rem 0.5rem;border-radius:999px;">Hidden from family</span>
                                    @endif
                                </div>
                                <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-bottom:0.5rem;">
                                    {{ $agency->city }}, {{ $agency->state }}
                                    @if($result->score_breakdown['distance_miles'] ?? null) &middot; {{ $result->score_breakdown['distance_miles'] }} mi @endif
                                    &middot; Confidence: {{ $explanation['confidence'] }}%
                                </div>
                                <div class="mb-2">
                                    @foreach(array_slice($explanation['reasons'], 0, 3) as $reason)
                                        <span style="font-size:0.75rem;color:var(--hb-gray-900);margin-right:0.75rem;"><i class="bi bi-check-circle-fill me-1" style="color:var(--hb-emerald-700);"></i>{{ $reason }}</span>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="collapse" data-bs-target="#adv-explain-{{ $result->id }}" style="font-size:0.75rem;">
                                    Full breakdown <i class="bi bi-chevron-down"></i>
                                </button>
                                <div class="collapse" id="adv-explain-{{ $result->id }}">
                                    <div style="background:var(--hb-gray-50);border-radius:0.625rem;padding:0.75rem;margin-top:0.5rem;font-size:0.775rem;">
                                        @foreach($result->score_breakdown['factors'] as $key => $factor)
                                            @if($factor['applicable'])
                                                <div class="d-flex justify-content-between">
                                                    <span>{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                                    <span class="fw-bold">{{ $factor['score'] }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-2" style="min-width:180px;">
                                <form method="POST" action="{{ route('advisor.leads.recommendations.approve', [$lead, $result]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm w-100 {{ $result->is_advisor_approved ? 'btn-primary' : 'btn-outline-primary' }}" style="border-radius:0.5rem;">
                                        <i class="bi bi-patch-check{{ $result->is_advisor_approved ? '-fill' : '' }} me-1"></i>{{ $result->is_advisor_approved ? 'Approved' : 'Approve' }}
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm w-100 {{ $result->is_hidden_by_advisor ? 'btn-secondary' : 'btn-outline-secondary' }}" style="border-radius:0.5rem;"
                                        data-bs-toggle="modal" data-bs-target="#hideModal-{{ $result->id }}">
                                    <i class="bi bi-eye-slash me-1"></i>{{ $result->is_hidden_by_advisor ? 'Unhide' : 'Hide from Family' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="hideModal-{{ $result->id }}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius:1rem;border:none;">
                            <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                                <h6 class="modal-title fw-bold">{{ $result->is_hidden_by_advisor ? 'Unhide' : 'Hide' }} {{ $agency->name }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="{{ route('advisor.leads.recommendations.hide', [$lead, $result]) }}">
                                @csrf
                                <div class="modal-body">
                                    <p style="font-size:0.85rem;color:var(--hb-gray-600);">
                                        This only controls what the family sees — it does not change the computed match score.
                                    </p>
                                    <label class="hb-form-label">Note (optional, internal only)</label>
                                    <textarea name="advisor_override_note" rows="2" class="hb-form-control">{{ $result->advisor_override_note }}</textarea>
                                </div>
                                <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                                    <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">{{ $result->is_hidden_by_advisor ? 'Unhide' : 'Hide' }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @push('scripts')
    <script>
        let draggedCard = null;
        document.querySelectorAll('.match-card').forEach(card => {
            card.addEventListener('dragstart', () => { draggedCard = card; card.style.opacity = '0.4'; });
            card.addEventListener('dragend', () => {
                card.style.opacity = card.classList.contains('hidden-match') ? '0.5' : '1';
                saveOrder();
            });
            card.addEventListener('dragover', (e) => {
                e.preventDefault();
                const list = document.getElementById('matchList');
                const afterElement = getDragAfterElement(list, e.clientY);
                if (!draggedCard) return;
                if (afterElement == null) {
                    list.appendChild(draggedCard);
                } else {
                    list.insertBefore(draggedCard, afterElement);
                }
            });
        });

        function getDragAfterElement(container, y) {
            const cards = [...container.querySelectorAll('.match-card:not([style*="opacity: 0.4"])')];
            return cards.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                }
                return closest;
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        function saveOrder() {
            const order = [...document.querySelectorAll('.match-card')].map(c => c.dataset.id);
            fetch('{{ route('advisor.leads.recommendations.reorder', $lead) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ order }),
            }).catch(() => {
                document.getElementById('advisorMatchError').textContent = 'Failed to save the new order.';
                document.getElementById('advisorMatchError').style.display = 'block';
            });
        }
    </script>
    @endpush
</x-advisor-layout>

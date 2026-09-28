<x-advisor-layout title="{{ $lead->family_name }}">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('advisor.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('advisor.leads.index') }}" class="hb-link">Lead Inbox</a></li>
        <li class="breadcrumb-item active">{{ $lead->family_name }}</li>
    @endslot

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
        $nextStatuses = $lead->status->allowedNextStatuses();
        $careSeeker = $lead->careSeeker;
        $latestAssessment = $careSeeker?->needsAssessments?->sortByDesc('completed_at')->first();
    @endphp

    {{-- Header --}}
    <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h5 class="fw-bold mb-0" style="color:var(--hb-gray-900);">{{ $lead->family_name }}</h5>
                        <span style="font-size:0.75rem;font-weight:600;background:{{ $c['bg'] }};color:{{ $c['text'] }};padding:0.2rem 0.6rem;border-radius:999px;">
                            {{ $lead->status->label() }}
                        </span>
                    </div>
                    <div style="font-size:0.85rem;color:var(--hb-gray-600);">
                        {{ $lead->source->label() }}
                        @if($careSeeker) &middot; Care Seeker: {{ $careSeeker->full_name }} @endif
                        @if($lead->advisor) &middot; Advisor: {{ $lead->advisor->user->name }} @endif
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @if($careSeeker)
                        <a href="{{ route('advisor.leads.recommendations.index', $lead) }}" class="btn btn-outline-primary btn-sm" style="border-radius:0.625rem;">
                            <i class="bi bi-stars me-1"></i>Recommendations
                        </a>
                    @endif
                    <a href="{{ route('advisor.inbox') }}?lead={{ $lead->id }}" class="btn btn-outline-secondary btn-sm" style="border-radius:0.625rem;">
                        <i class="bi bi-chat-dots me-1"></i>Message Family
                    </a>
                </div>
            </div>

            @if(!empty($nextStatuses))
                <hr style="border-color:var(--hb-gray-200);">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($nextStatuses as $next)
                        @if($next->value === 'closed_lost')
                            <button type="button" class="btn btn-outline-danger btn-sm" style="border-radius:0.625rem;" data-bs-toggle="modal" data-bs-target="#closeLeadModal">
                                <i class="bi bi-x-circle me-1"></i>{{ $next->label() }}
                            </button>
                        @else
                            <form method="POST" action="{{ route('advisor.leads.status', $lead) }}" class="d-inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="status" value="{{ $next->value }}">
                                <button type="submit" class="btn btn-outline-primary btn-sm" style="border-radius:0.625rem;">
                                    <i class="bi bi-arrow-right-circle me-1"></i>Move to {{ $next->label() }}
                                </button>
                            </form>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Family & Care Seeker Information --}}
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);font-size:0.875rem;">
                                <i class="bi bi-people me-2" style="color:var(--hb-emerald-700);"></i>Family Information
                            </h6>
                            <dl class="mb-0" style="font-size:0.85rem;">
                                <dt style="color:var(--hb-gray-600);">Contact</dt>
                                <dd>{{ $lead->family->user->name }}</dd>
                                <dt style="color:var(--hb-gray-600);">Email</dt>
                                <dd>{{ $lead->family->user->email }}</dd>
                                <dt style="color:var(--hb-gray-600);">Phone</dt>
                                <dd>{{ $lead->family->phone ?? 'Not provided' }}</dd>
                                <dt style="color:var(--hb-gray-600);">Relationship to Care Seeker</dt>
                                <dd class="mb-0">{{ $lead->family->relationship_to_seeker ?? 'Not specified' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);font-size:0.875rem;">
                                <i class="bi bi-person-heart me-2" style="color:var(--hb-emerald-700);"></i>Care Seeker Information
                            </h6>
                            @if($careSeeker)
                                <dl class="mb-0" style="font-size:0.85rem;">
                                    <dt style="color:var(--hb-gray-600);">Name / Age</dt>
                                    <dd>{{ $careSeeker->full_name }}{{ $careSeeker->age ? ', age '.$careSeeker->age : '' }}</dd>
                                    <dt style="color:var(--hb-gray-600);">Care Type Needed</dt>
                                    <dd>{{ $careSeeker->care_type_needed?->label() ?? 'Not specified' }}</dd>
                                    <dt style="color:var(--hb-gray-600);">Budget</dt>
                                    <dd>
                                        @if($careSeeker->budget_min || $careSeeker->budget_max)
                                            ${{ number_format($careSeeker->budget_min ?? 0) }} – ${{ number_format($careSeeker->budget_max ?? 0) }}/mo
                                        @else Not specified @endif
                                    </dd>
                                    <dt style="color:var(--hb-gray-600);">Mobility / Memory</dt>
                                    <dd>{{ $careSeeker->mobility?->label() ?? '—' }} / {{ $careSeeker->memory_status?->label() ?? '—' }}</dd>
                                    <dt style="color:var(--hb-gray-600);">Move-in Timeline</dt>
                                    <dd class="mb-0">{{ $careSeeker->move_in_timeline?->label() ?? 'Not specified' }}</dd>
                                </dl>
                            @else
                                <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No care seeker profile attached to this lead yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Needs Assessment Summary --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);font-size:0.875rem;">
                        <i class="bi bi-clipboard2-pulse me-2" style="color:var(--hb-emerald-700);"></i>Needs Assessment
                    </h6>
                    @if($latestAssessment && $latestAssessment->status === 'completed')
                        <div class="hb-alert hb-alert-success mb-3" style="font-size:0.8rem;">
                            Completed {{ $latestAssessment->completed_at->format('M d, Y') }}
                        </div>
                        <div class="row g-2">
                            @foreach(($latestAssessment->score_profile ?? []) as $section => $answers)
                                <div class="col-md-6">
                                    <div style="font-size:0.7rem;font-weight:700;color:var(--hb-emerald-700);text-transform:uppercase;">{{ str_replace('_', ' ', $section) }}</div>
                                    @foreach($answers as $code => $value)
                                        <div style="font-size:0.8rem;color:var(--hb-gray-900);">
                                            {{ str_replace('_', ' ', $code) }}: <strong>{{ is_array($value) ? implode(', ', $value) : $value }}</strong>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @elseif($latestAssessment)
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">
                            In progress (step {{ $latestAssessment->current_step }} of {{ \App\Services\Family\NeedsAssessmentService::TOTAL_STEPS }}).
                        </p>
                    @else
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">Not started by the family yet.</p>
                    @endif
                </div>
            </div>

            {{-- Documents --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);font-size:0.875rem;">
                        <i class="bi bi-file-earmark-lock me-2" style="color:var(--hb-emerald-700);"></i>Documents
                    </h6>
                    @forelse($careSeeker?->documents ?? [] as $document)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div>
                                <i class="bi bi-file-earmark-text me-1" style="color:var(--hb-gray-600);"></i>
                                {{ $document->original_filename }}
                                <span class="hb-badge-verified ms-2">{{ $document->document_type->label() }}</span>
                            </div>
                            <a href="{{ route('advisor.leads.documents.download', [$lead, $document]) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:0.5rem;">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No documents uploaded by the family yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Lead Notes / Internal Comments --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Notes &amp; Comments</h6>

                    <form method="POST" action="{{ route('advisor.leads.notes.store', $lead) }}" class="mb-3">
                        @csrf
                        <div class="row g-2 mb-2">
                            <div class="col-md-5">
                                <select name="note_type" class="hb-form-control">
                                    <option value="call">Call</option>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="general">General</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" name="is_internal" value="1" id="isInternal">
                                    <label class="form-check-label" for="isInternal" style="font-size:0.8rem;">Internal comment (staff-only)</label>
                                </div>
                            </div>
                        </div>
                        <textarea name="content" rows="2" class="hb-form-control mb-2" placeholder="Add a note..." required></textarea>
                        <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.625rem;"><i class="bi bi-plus-lg me-1"></i>Add</button>
                    </form>

                    <hr style="border-color:var(--hb-gray-200);">

                    @forelse($lead->notes as $note)
                        <div class="mb-3 pb-3" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="hb-badge-verified">{{ $note->note_type->label() }}</span>
                                @if($note->is_internal)
                                    <span style="font-size:0.7rem;font-weight:600;background:var(--hb-gray-200);color:var(--hb-gray-600);padding:0.15rem 0.5rem;border-radius:999px;">
                                        <i class="bi bi-lock-fill me-1"></i>Internal
                                    </span>
                                @endif
                            </div>
                            <p style="font-size:0.875rem;color:var(--hb-gray-900);margin-bottom:0.25rem;">{{ $note->content }}</p>
                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $note->advisor->user->name }} &middot; {{ $note->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No notes yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Tour Scheduling --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="color:var(--hb-gray-900);">Tour Scheduling</h6>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTourModal" style="border-radius:0.625rem;">
                            <i class="bi bi-plus-lg me-1"></i>Schedule Tour
                        </button>
                    </div>
                    @forelse($lead->tourRequests as $tour)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div>
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $tour->agency->name ?? 'Agency' }}</div>
                                <div style="font-size:0.775rem;color:var(--hb-gray-600);">
                                    {{ $tour->requested_date->format('M d, Y') }} @if($tour->requested_time_window) &middot; {{ $tour->requested_time_window }} @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="hb-badge-verified">{{ ucfirst($tour->status->value) }}</span>
                                <a href="{{ route('advisor.tours.index') }}" class="btn btn-sm btn-link p-0" style="font-size:0.75rem;">Manage</a>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No tours scheduled yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Assignment History --}}
            <div class="card mb-4" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);font-size:0.875rem;">
                        <i class="bi bi-person-check me-2" style="color:var(--hb-emerald-700);"></i>Assignment History
                    </h6>
                    @forelse($lead->assignmentHistory as $entry)
                        <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);font-size:0.85rem;">
                            <span>{{ $entry->advisor->user->name }}</span>
                            <span style="color:var(--hb-gray-600);font-size:0.775rem;">
                                {{ $entry->assigned_at->format('M d, Y') }}
                                @if($entry->unassigned_at) – {{ $entry->unassigned_at->format('M d, Y') }} @endif
                            </span>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);margin-bottom:0;">No assignment history.</p>
                    @endforelse
                </div>
            </div>

            {{-- Complete Lead Timeline --}}
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Complete Lead Timeline</h6>
                    @forelse($timeline as $entry)
                        <div class="d-flex gap-3 py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div style="width:32px;height:32px;background:{{ $entry['color'] }}1a;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi {{ $entry['icon'] }}" style="font-size:0.8rem;color:{{ $entry['color'] }};"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:0.875rem;font-weight:600;color:var(--hb-gray-900);">{{ $entry['title'] }}</div>
                                @if($entry['detail'])
                                    <div style="font-size:0.8rem;color:var(--hb-gray-600);margin-top:0.2rem;">{{ Str::limit($entry['detail'], 150) }}</div>
                                @endif
                                <div style="font-size:0.75rem;color:var(--hb-gray-600);margin-top:0.2rem;">
                                    @if($entry['actor']) {{ $entry['actor'] }} &middot; @endif {{ $entry['at']->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:0.875rem;color:var(--hb-gray-600);">No activity recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar: Tasks --}}
        <div class="col-lg-4">
            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);position:sticky;top:1rem;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">
                        <i class="bi bi-list-task me-2" style="color:var(--hb-emerald-700);"></i>Tasks &amp; Follow-ups
                    </h6>

                    <form method="POST" action="{{ route('advisor.tasks.store') }}" class="mb-3">
                        @csrf
                        <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                        <input type="text" name="title" class="hb-form-control mb-2" placeholder="Task title" required>
                        <input type="datetime-local" name="due_at" class="hb-form-control mb-2" required>
                        <select name="priority" class="hb-form-control mb-2">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm w-100" style="border-radius:0.625rem;">
                            <i class="bi bi-plus-lg me-1"></i>Add Task
                        </button>
                    </form>

                    <hr style="border-color:var(--hb-gray-200);">

                    @forelse($lead->tasks as $task)
                        <div class="d-flex align-items-start justify-content-between py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                            <div>
                                <div style="font-size:0.85rem;font-weight:600;color:{{ $task->completed_at ? 'var(--hb-gray-600)' : 'var(--hb-gray-900)' }};{{ $task->completed_at ? 'text-decoration:line-through;' : '' }}">
                                    <i class="bi bi-flag-fill me-1" style="color:{{ $task->priority->color() }};font-size:0.7rem;"></i>{{ $task->title }}
                                </div>
                                <div style="font-size:0.75rem;color:{{ !$task->completed_at && $task->due_at->isPast() ? 'var(--hb-danger)' : 'var(--hb-gray-600)' }};">
                                    Due {{ $task->due_at->format('M d, g:ia') }}
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                @unless($task->completed_at)
                                    <form method="POST" action="{{ route('advisor.tasks.complete', $task) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-link text-success p-0"><i class="bi bi-check-circle"></i></button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('advisor.tasks.destroy', $task) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:0.85rem;color:var(--hb-gray-600);">No tasks yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Add Tour Modal --}}
    <div class="modal fade" id="addTourModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold">Schedule Tour</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('advisor.leads.tours.store', $lead) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="hb-form-label">Agency</label>
                            <select name="agency_id" class="hb-form-control" required>
                                <option value="">Select an agency...</option>
                                @foreach(\App\Models\Agency::published()->orderBy('name')->get() as $agency)
                                    <option value="{{ $agency->id }}">{{ $agency->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="hb-form-label">Date</label>
                                <input type="date" name="requested_date" class="hb-form-control" required min="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-6">
                                <label class="hb-form-label">Time window</label>
                                <input type="text" name="requested_time_window" class="hb-form-control" placeholder="e.g. 2-4 PM">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="hb-form-label">Notes</label>
                            <textarea name="notes" rows="2" class="hb-form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="border-radius:0.75rem;">Schedule Tour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Close Lead (Closed Lost) Modal --}}
    <div class="modal fade" id="closeLeadModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header" style="border-bottom:1px solid var(--hb-gray-200);">
                    <h6 class="modal-title fw-bold text-danger">Close Lead — Lost</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('advisor.leads.status', $lead) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="status" value="closed_lost">
                    <div class="modal-body">
                        <label class="hb-form-label">Reason</label>
                        <textarea name="reason" rows="3" class="hb-form-control" required minlength="5"></textarea>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--hb-gray-200);">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:0.75rem;">Cancel</button>
                        <button type="submit" class="btn btn-danger" style="border-radius:0.75rem;">Close Lead</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-advisor-layout>

<?php

namespace App\Http\Controllers\Advisor;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Advisor;
use App\Models\Lead;
use App\Services\Advisor\LeadPipelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Dedicated Kanban board — distinct from Lead Inbox (a filterable table).
 * Both read from the same underlying Lead query scope (own leads + team's
 * leads for an Advisor Manager); this controller groups by status instead
 * of paginating a flat list, and adds the drag-and-drop stage-change
 * endpoint.
 */
class PipelineController extends Controller
{
    public function __construct(
        private readonly LeadPipelineService $pipeline,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        $advisor = Advisor::where('user_id', $request->user()->id)->firstOrFail();

        $query = Lead::with(['family.user', 'careSeeker'])
            ->where(function ($q) use ($advisor) {
                $q->where('advisor_id', $advisor->id);
                if ($advisor->teamMembers()->exists()) {
                    $q->orWhereIn('advisor_id', $advisor->teamMembers()->pluck('id'));
                }
            })
            ->latest();

        $leads = $query->get();
        $columns = collect(LeadStatus::cases())->mapWithKeys(
            fn (LeadStatus $status) => [$status->value => $leads->where('status', $status)]
        );

        $stats = [
            'total' => $leads->count(),
            'open' => $leads->filter(fn (Lead $l) => $l->status->isOpen())->count(),
            'converted' => $leads->where('status', LeadStatus::Converted)->count(),
            'closed_lost' => $leads->where('status', LeadStatus::ClosedLost)->count(),
        ];

        return view('advisor.pipeline', compact('columns', 'stats'));
    }

    /**
     * Drag-and-drop stage change — AJAX endpoint. Returns JSON so the
     * client can revert the card's position if the move is illegal,
     * rather than a full page redirect breaking the drag interaction.
     */
    public function move(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $request->validate([
            'status' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $target = LeadStatus::tryFrom($request->status);
        if (!$target) {
            return response()->json(['message' => 'Invalid status.'], 422);
        }

        if ($target === LeadStatus::ClosedLost && !$request->filled('reason')) {
            return response()->json(['message' => 'A reason is required to close a lead as lost.', 'requires_reason' => true], 422);
        }

        try {
            $this->pipeline->transition($lead, $target, $request->user(), $request->reason);
        } catch (ValidationException $e) {
            // ValidationException::getMessage() returns a generic "The
            // given data was invalid" string, not the specific message
            // set via withMessages() — the actual text lives in errors().
            $message = collect($e->errors())->flatten()->first() ?? 'That move is not allowed.';
            return response()->json(['message' => $message], 422);
        }

        return response()->json(['status' => $target->value, 'label' => $target->label()]);
    }
}

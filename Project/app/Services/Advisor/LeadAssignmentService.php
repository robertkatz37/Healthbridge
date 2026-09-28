<?php

namespace App\Services\Advisor;

use App\Models\Advisor;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\Advisor\LeadAssigned;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Cache;

/**
 * Handles both automatic (round robin / territory-based) and manual lead
 * assignment. Every assignment or reassignment:
 *  1. Closes out the prior active advisor_assignments row (if any)
 *  2. Creates a new advisor_assignments row (the audit trail — see
 *     migration 2026_07_07_000007 for why this repurposes that existing
 *     table rather than introducing a parallel one)
 *  3. Updates leads.advisor_id/assigned_at directly
 *  4. Notifies the newly-assigned advisor
 */
class LeadAssignmentService
{
    private const ROUND_ROBIN_CURSOR_KEY = 'lead_assignment:round_robin_cursor';

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /**
     * Territory-based first (matches the lead's care seeker's preferred
     * city/state against advisor_territories); falls back to global round
     * robin if no advisor covers that territory, so a lead is never left
     * unassigned just because it's outside every advisor's stated area.
     */
    public function autoAssign(Lead $lead, ?User $actor = null): ?Advisor
    {
        $advisor = $this->matchByTerritory($lead) ?? $this->nextRoundRobinAdvisor();

        if (!$advisor) {
            return null;
        }

        $this->assign($lead, $advisor, $actor, auto: true);

        return $advisor;
    }

    public function matchByTerritory(Lead $lead): ?Advisor
    {
        $careSeeker = $lead->careSeeker;
        if (!$careSeeker || !$careSeeker->preferred_city || !$careSeeker->preferred_state) {
            return null;
        }

        $advisorIds = \App\Models\AdvisorTerritory::inCity($careSeeker->preferred_city, $careSeeker->preferred_state)
            ->pluck('advisor_id');

        if ($advisorIds->isEmpty()) {
            return null;
        }

        return Advisor::active()
            ->whereIn('id', $advisorIds)
            ->inRandomOrder()
            ->first();
    }

    /**
     * Cycles through active advisors in a stable order, remembering the
     * last-assigned advisor's position in the cache so consecutive calls
     * (across requests) continue the rotation rather than restarting it.
     */
    public function nextRoundRobinAdvisor(): ?Advisor
    {
        $advisors = Advisor::active()->orderBy('id')->get();
        if ($advisors->isEmpty()) {
            return null;
        }

        $lastId = Cache::get(self::ROUND_ROBIN_CURSOR_KEY);
        $lastIndex = $lastId ? $advisors->search(fn (Advisor $a) => $a->id === $lastId) : false;
        $nextIndex = ($lastIndex === false) ? 0 : ($lastIndex + 1) % $advisors->count();

        $next = $advisors->values()->get($nextIndex);

        Cache::forever(self::ROUND_ROBIN_CURSOR_KEY, $next->id);

        return $next;
    }

    public function assign(Lead $lead, Advisor $advisor, ?User $actor = null, bool $auto = false): void
    {
        $previousAdvisorId = $lead->advisor_id;

        if ($previousAdvisorId) {
            $lead->assignmentHistory()->active()->update(['unassigned_at' => now()]);
        }

        $lead->assignmentHistory()->create([
            'advisor_id' => $advisor->id,
            // family_id is NOT NULL on this table's original Phase 2
            // schema (predates lead_id) — always populated from the
            // lead's own family_id so this insert satisfies that
            // constraint regardless of which column a future reader
            // queries by.
            'family_id' => $lead->family_id,
            'assigned_at' => now(),
        ]);

        $lead->update([
            'advisor_id' => $advisor->id,
            'assigned_at' => now(),
            'territory_city' => $lead->careSeeker?->preferred_city,
            'territory_state' => $lead->careSeeker?->preferred_state,
        ]);

        if ($lead->status->value === 'new') {
            app(LeadPipelineService::class)->transition($lead, \App\Enums\LeadStatus::Assigned, $actor);
        }

        activity()
            ->causedBy($actor)
            ->performedOn($lead)
            ->withProperties(['advisor_id' => $advisor->id, 'auto' => $auto, 'previous_advisor_id' => $previousAdvisorId])
            ->log($auto ? 'Lead auto-assigned' : 'Lead manually assigned');

        // Queries directly rather than $advisor->user, which could return
        // a stale cached relation instance if it was ever accessed
        // earlier in this object's lifecycle (e.g. before a notification
        // preference change) — see DATABASE_DECISIONS.md §13 for the
        // established reasoning behind this pattern, applied consistently
        // across the project wherever a policy/service reads a
        // belongs-to relation that may have already been touched.
        User::find($advisor->user_id)?->notify(new LeadAssigned($lead));
    }
}

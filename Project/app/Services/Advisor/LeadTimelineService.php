<?php

namespace App\Services\Advisor;

use App\Models\Advisor;
use App\Models\Lead;
use Illuminate\Support\Collection;

/**
 * Unifies every event type touching a Lead — status changes, notes,
 * tasks (created/completed), tours, and messages — into a single,
 * chronologically sorted timeline. Used both by a single Lead's "Complete
 * Lead Timeline" section (Lead Details) and by the advisor-wide Activity
 * Timeline page (aggregating across every lead the advisor/their team
 * owns), so the event-shaping logic lives in one place rather than being
 * duplicated between two views.
 */
class LeadTimelineService
{
    public function forLead(Lead $lead): Collection
    {
        $lead->loadMissing(['statusHistory.changedBy', 'notes.advisor.user', 'tasks', 'tourRequests.agency', 'conversations.messages.sender']);

        return $this->buildEntries($lead);
    }

    /**
     * Aggregates the timeline across every lead an advisor (or their
     * team, if they manage one) owns — capped and most-recent-first, for
     * the dedicated Activity Timeline page.
     */
    public function forAdvisor(Advisor $advisor, int $limit = 100): Collection
    {
        $leadIds = Lead::where(function ($q) use ($advisor) {
            $q->where('advisor_id', $advisor->id);
            if ($advisor->teamMembers()->exists()) {
                $q->orWhereIn('advisor_id', $advisor->teamMembers()->pluck('id'));
            }
        })->pluck('id');

        $leads = Lead::whereIn('id', $leadIds)
            ->with(['family.user', 'statusHistory.changedBy', 'notes.advisor.user', 'tasks', 'tourRequests.agency', 'conversations.messages.sender'])
            ->get();

        return $leads
            ->flatMap(fn (Lead $lead) => $this->buildEntries($lead, includeFamilyName: true))
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }

    private function buildEntries(Lead $lead, bool $includeFamilyName = false): Collection
    {
        $entries = collect();

        foreach ($lead->statusHistory as $entry) {
            $entries->push([
                'type' => 'status_change',
                'icon' => 'bi-arrow-right-circle',
                'color' => 'var(--hb-emerald-700)',
                'title' => ($entry->from_status ? ucfirst(str_replace('_', ' ', $entry->from_status)) . ' → ' : '') . ucfirst(str_replace('_', ' ', $entry->to_status)),
                'detail' => $entry->reason,
                'actor' => $entry->changedBy?->name ?? 'System',
                'at' => $entry->created_at,
                'lead' => $lead,
                'family_name' => $includeFamilyName ? $lead->family_name : null,
            ]);
        }

        foreach ($lead->notes as $note) {
            $entries->push([
                'type' => $note->is_internal ? 'internal_comment' : 'note',
                'icon' => $note->is_internal ? 'bi-lock-fill' : 'bi-journal-text',
                'color' => 'var(--hb-gray-600)',
                'title' => ($note->is_internal ? 'Internal comment' : 'Note') . ' (' . $note->note_type->label() . ')',
                'detail' => $note->content,
                'actor' => $note->advisor->user->name,
                'at' => $note->created_at,
                'lead' => $lead,
                'family_name' => $includeFamilyName ? $lead->family_name : null,
            ]);
        }

        foreach ($lead->tasks as $task) {
            $entries->push([
                'type' => 'task_created',
                'icon' => 'bi-list-task',
                'color' => 'var(--hb-warning)',
                'title' => 'Task created: ' . $task->title,
                'detail' => null,
                'actor' => null,
                'at' => $task->created_at,
                'lead' => $lead,
                'family_name' => $includeFamilyName ? $lead->family_name : null,
            ]);
            if ($task->completed_at) {
                $entries->push([
                    'type' => 'task_completed',
                    'icon' => 'bi-check2-square',
                    'color' => 'var(--hb-success)',
                    'title' => 'Task completed: ' . $task->title,
                    'detail' => null,
                    'actor' => null,
                    'at' => $task->completed_at,
                    'lead' => $lead,
                    'family_name' => $includeFamilyName ? $lead->family_name : null,
                ]);
            }
        }

        foreach ($lead->tourRequests as $tour) {
            $entries->push([
                'type' => 'tour_scheduled',
                'icon' => 'bi-calendar-check',
                'color' => 'var(--hb-gold-500)',
                'title' => 'Tour scheduled with ' . ($tour->agency->name ?? 'agency') . ' on ' . $tour->requested_date->format('M d, Y'),
                'detail' => null,
                'actor' => null,
                'at' => $tour->created_at,
                'lead' => $lead,
                'family_name' => $includeFamilyName ? $lead->family_name : null,
            ]);
        }

        foreach ($lead->conversations as $conversation) {
            foreach ($conversation->messages as $message) {
                $entries->push([
                    'type' => 'message',
                    'icon' => 'bi-chat-dots',
                    'color' => 'var(--hb-emerald-500)',
                    'title' => 'Message sent',
                    'detail' => $message->body,
                    'actor' => $message->sender->name,
                    'at' => $message->created_at,
                    'lead' => $lead,
                    'family_name' => $includeFamilyName ? $lead->family_name : null,
                ]);
            }
        }

        return $entries->sortByDesc('at')->values();
    }
}

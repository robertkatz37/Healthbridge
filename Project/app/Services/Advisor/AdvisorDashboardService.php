<?php

namespace App\Services\Advisor;

use App\Enums\LeadStatus;
use App\Models\Advisor;
use App\Models\Lead;

class AdvisorDashboardService
{
    public function summary(Advisor $advisor): array
    {
        $leads = $advisor->leads();

        $totalLeads = (clone $leads)->count();
        $convertedTotal = (clone $leads)->where('status', LeadStatus::Converted->value)->count();

        // "Active" leads: assigned and being actively worked, excluding
        // the raw "New"/unassigned-adjacent stage and any terminal status.
        $activeStatuses = ['contacted', 'assessment_reviewed', 'shortlisted', 'tour_scheduled', 'follow_up', 'move_in_confirmed'];

        return [
            'total_leads' => $totalLeads,
            'open_leads' => (clone $leads)->open()->count(),
            'new_leads' => (clone $leads)->where('status', LeadStatus::New->value)->count(),
            'active_leads' => (clone $leads)->whereIn('status', $activeStatuses)->count(),
            'overdue_leads' => (clone $leads)->open()->whereHas('tasks', fn ($q) => $q->overdue())->count(),
            'converted_leads' => $convertedTotal,
            'converted_this_month' => (clone $leads)
                ->where('status', LeadStatus::Converted->value)
                ->whereMonth('converted_at', now()->month)
                ->whereYear('converted_at', now()->year)
                ->count(),
            'conversion_rate' => $totalLeads > 0 ? round(($convertedTotal / $totalLeads) * 100, 1) : 0.0,
            'average_response_time_hours' => $this->averageResponseTimeHours($advisor),
            'tasks_due_today' => $advisor->tasks()->dueToday()->count(),
            'tasks_overdue' => $advisor->tasks()->overdue()->count(),
            'tasks_pending' => $advisor->tasks()->pending()->count(),
            'tasks_completed' => $advisor->tasks()->completed()->count(),
            'tours_today' => \App\Models\TourRequest::whereIn('lead_id', (clone $leads)->pluck('id'))
                ->whereDate('requested_date', now()->toDateString())->count(),
            'tours_upcoming' => \App\Models\TourRequest::whereIn('lead_id', (clone $leads)->pluck('id'))
                ->upcoming()->count(),
        ];
    }

    /**
     * Average time (in hours) from assignment to first "Contacted"
     * transition, across this advisor's leads that have reached that
     * stage — a rough proxy for "Average Response Time."
     */
    private function averageResponseTimeHours(Advisor $advisor): ?float
    {
        $leadIds = $advisor->leads()->pluck('id');

        $samples = \App\Models\LeadStatusHistory::whereIn('lead_id', $leadIds)
            ->where('to_status', 'contacted')
            ->get()
            ->map(function ($entry) {
                $assignedAt = \App\Models\LeadStatusHistory::where('lead_id', $entry->lead_id)
                    ->where('to_status', 'assigned')
                    ->oldest()
                    ->first()?->created_at;

                return $assignedAt ? $assignedAt->diffInMinutes($entry->created_at) / 60 : null;
            })
            // A bare filter() uses PHP's default truthy check, which
            // would incorrectly strip out a genuine 0.0-hour (near-
            // instant) response time along with the real nulls — caught
            // via smoke testing before shipping. Filtering on strict
            // null-ness instead keeps 0 as a valid data point.
            ->filter(fn ($value) => $value !== null);

        return $samples->isEmpty() ? null : round($samples->avg(), 1);
    }

    /**
     * Team-wide summary for an Advisor Manager — aggregates across every
     * advisor on their team (advisor_manager_id chain), not just their own.
     */
    public function teamSummary(Advisor $manager): array
    {
        $teamIds = $manager->teamMembers()->pluck('id')->push($manager->id);

        return [
            'team_size' => $manager->teamMembers()->count(),
            'total_open_leads' => Lead::whereIn('advisor_id', $teamIds)->open()->count(),
            'unassigned_leads' => Lead::unassigned()->open()->count(),
        ];
    }
}

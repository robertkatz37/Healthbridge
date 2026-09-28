<?php

namespace App\Services\Referral;

use App\Enums\ReferralStatus;
use App\Enums\TourRequestStatus;
use App\Models\Referral;
use App\Models\TourRequest;
use App\Models\User;
use App\Notifications\Tour\TourCancelled;
use App\Notifications\Tour\TourScheduled as TourScheduledNotification;
use App\Notifications\Tour\TourUpdated;
use Illuminate\Support\Collection;

/**
 * Owns tour scheduling/rescheduling/cancellation/completion for a
 * Referral — a Referral's tour, distinct from the broader per-Lead tour
 * list built in Phase 11 (a Lead can span multiple Referrals, and a
 * tour is always for one specific agency). Also transitions the parent
 * Referral's own pipeline stage where appropriate (scheduling a tour
 * moves the Referral to TourScheduled, completing one moves it to
 * TourCompleted), keeping the Referral status and its tour in sync
 * rather than requiring the advisor to update both separately.
 */
class TourManagementService
{
    public function __construct(
        private readonly ReferralPipelineService $pipeline,
    ) {}

    public function schedule(Referral $referral, string $date, ?string $timeWindow, ?string $notes, ?User $actor = null): TourRequest
    {
        $tour = $referral->tourRequests()->create([
            'family_id' => $referral->family_id,
            'care_seeker_id' => $referral->care_seeker_id,
            'agency_id' => $referral->agency_id,
            'lead_id' => $referral->lead_id,
            'requested_date' => $date,
            'requested_time_window' => $timeWindow,
            'notes' => $notes,
        ]);

        if ($referral->status === ReferralStatus::AgencyAccepted) {
            $this->pipeline->transition($referral, ReferralStatus::TourScheduled, $actor);
        }

        $this->recipients($referral)->each(fn (User $u) => $u->notify(new TourScheduledNotification($tour)));

        activity()->causedBy($actor)->performedOn($tour)->log('Tour scheduled');

        return $tour;
    }

    public function reschedule(TourRequest $tour, string $newDate, ?string $newTimeWindow, ?User $actor = null): TourRequest
    {
        $oldDate = $tour->requested_date->format('M d, Y');

        $tour->update([
            'requested_date' => $newDate,
            'requested_time_window' => $newTimeWindow,
            'status' => TourRequestStatus::Requested->value,
            'confirmed_at' => null,
        ]);

        $summary = "Rescheduled from {$oldDate} to " . \Illuminate\Support\Carbon::parse($newDate)->format('M d, Y') . '.';

        if ($tour->referral) {
            $this->recipients($tour->referral)->each(fn (User $u) => $u->notify(new TourUpdated($tour, $summary)));
        }

        activity()->causedBy($actor)->performedOn($tour)->log('Tour rescheduled');

        return $tour;
    }

    public function confirm(TourRequest $tour, ?User $actor = null): TourRequest
    {
        $tour->update(['status' => TourRequestStatus::Confirmed->value, 'confirmed_at' => now()]);

        if ($tour->referral) {
            $this->recipients($tour->referral)->each(fn (User $u) => $u->notify(new TourUpdated($tour, 'Confirmed by the agency.')));
        }

        return $tour;
    }

    public function complete(TourRequest $tour, ?User $actor = null): TourRequest
    {
        $tour->update(['status' => TourRequestStatus::Completed->value, 'completed_at' => now()]);

        if ($tour->referral && $tour->referral->status === ReferralStatus::TourScheduled) {
            $this->pipeline->transition($tour->referral, ReferralStatus::TourCompleted, $actor);
        }

        activity()->causedBy($actor)->performedOn($tour)->log('Tour marked completed');

        return $tour;
    }

    public function cancel(TourRequest $tour, ?User $actor = null): TourRequest
    {
        $tour->update(['status' => TourRequestStatus::Cancelled->value, 'cancelled_at' => now()]);

        if ($tour->referral) {
            $this->recipients($tour->referral)->each(fn (User $u) => $u->notify(new TourCancelled($tour)));
        }

        activity()->causedBy($actor)->performedOn($tour)->log('Tour cancelled');

        return $tour;
    }

    /**
     * Family + Advisor always; Agency only once they've actually
     * accepted the referral (before that point they wouldn't have a
     * tour to know about in the first place, since scheduling only
     * happens after acceptance in the normal flow — but this stays
     * defensive in case a tour is scheduled slightly out of the typical
     * order).
     */
    private function recipients(Referral $referral): Collection
    {
        return collect([$referral->family->user, $referral->advisor->user, $referral->agency->user])->filter();
    }
}

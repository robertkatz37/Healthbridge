<?php

namespace App\Policies;

use App\Models\CareSeekerDocument;
use App\Models\Family;
use App\Models\User;

class CareSeekerDocumentPolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        if ($actor->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function view(User $actor, CareSeekerDocument $document): bool
    {
        return $this->ownedByActor($actor, $document) || $this->assignedAdvisorOrManager($actor, $document);
    }

    public function create(User $actor): bool
    {
        return $actor->hasRole('family');
    }

    public function delete(User $actor, CareSeekerDocument $document): bool
    {
        return $this->ownedByActor($actor, $document);
    }

    private function ownedByActor(User $actor, CareSeekerDocument $document): bool
    {
        $careSeeker = $document->careSeeker;
        return $careSeeker && Family::where('user_id', $actor->id)->where('id', $careSeeker->family_id)->exists();
    }

    /**
     * The advisor currently assigned to a Lead for this care seeker (or
     * that advisor's manager) can view family-uploaded documents — needed
     * to coordinate care (e.g. sharing a medical record with a shortlisted
     * agency). Added during the Phase 11 completion pass: "Documents" was
     * an explicit Lead Details requirement, and the family's private
     * document vault (Phase 9) is the actual source for that section.
     */
    private function assignedAdvisorOrManager(User $actor, CareSeekerDocument $document): bool
    {
        $careSeeker = $document->careSeeker;
        if (!$careSeeker) {
            return false;
        }

        $advisor = \App\Models\Advisor::where('user_id', $actor->id)->first();
        if (!$advisor) {
            return false;
        }

        $leadAdvisorIds = \App\Models\Lead::where('care_seeker_id', $careSeeker->id)
            ->whereNotNull('advisor_id')
            ->pluck('advisor_id');

        if ($leadAdvisorIds->contains($advisor->id)) {
            return true;
        }

        return \App\Models\Advisor::whereIn('id', $leadAdvisorIds)
            ->where('advisor_manager_id', $advisor->id)
            ->exists();
    }
}

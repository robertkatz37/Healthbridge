<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        if ($actor->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $actor): bool
    {
        return true; // Published reviews are public.
    }

    public function view(User $actor, Review $review): bool
    {
        if ($review->status->value === 'published') {
            return true;
        }
        return $actor->can('reviews.moderate') || $actor->family?->id === $review->family_id;
    }

    public function create(User $actor): bool
    {
        return $actor->can('reviews.submit');
    }

    public function update(User $actor, Review $review): bool
    {
        // Authors may update their own pending review before it's moderated.
        if ($actor->family?->id === $review->family_id
            && $review->status->value === 'pending_moderation') {
            return true;
        }
        return $actor->can('reviews.moderate');
    }

    public function delete(User $actor, Review $review): bool
    {
        return $actor->can('reviews.moderate');
    }

    public function moderate(User $actor, Review $review): bool
    {
        return $actor->can('reviews.moderate');
    }

    public function reply(User $actor, Review $review): bool
    {
        // Agency owner/staff may post one reply per review for their own agency.
        return $actor->agency?->id === $review->agency_id
            || $actor->can('reviews.moderate');
    }
}

<?php

namespace App\Policies;

use App\Models\CmsPage;
use App\Models\User;

class CmsPagePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('cms.manage');
    }

    public function create(User $actor): bool
    {
        return $actor->can('cms.manage');
    }

    public function update(User $actor, CmsPage $page): bool
    {
        return $actor->can('cms.manage');
    }

    public function delete(User $actor, CmsPage $page): bool
    {
        return $actor->can('cms.manage');
    }
}

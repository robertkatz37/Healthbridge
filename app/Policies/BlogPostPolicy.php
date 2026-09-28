<?php

namespace App\Policies;

use App\Models\BlogPost;
use App\Models\User;

class BlogPostPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('cms.manage');
    }

    public function create(User $actor): bool
    {
        return $actor->can('cms.manage');
    }

    public function update(User $actor, BlogPost $post): bool
    {
        return $actor->can('cms.manage');
    }

    public function delete(User $actor, BlogPost $post): bool
    {
        return $actor->can('cms.manage');
    }
}

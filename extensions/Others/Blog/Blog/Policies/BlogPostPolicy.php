<?php

namespace Paymenter\Extensions\Others\Blog\Policies;

use App\Models\User;
use App\Policies\BasePolicy;
use Paymenter\Extensions\Others\Blog\Models\BlogPost;

class BlogPostPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.blog.view');
    }

    public function view(User $user, BlogPost $post): bool
    {
        return $user->hasPermission('admin.blog.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.blog.create');
    }

    public function update(User $user, BlogPost $post): bool
    {
        return $user->hasPermission('admin.blog.update');
    }

    public function delete(User $user, BlogPost $post): bool
    {
        return $user->hasPermission('admin.blog.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermission('admin.blog.delete');
    }
}

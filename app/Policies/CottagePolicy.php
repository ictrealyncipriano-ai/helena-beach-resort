<?php

namespace App\Policies;

use App\Models\Cottage;
use App\Models\User;

/**
 * Authorization for the admin cottages area.
 *
 * Mirrors AdminMiddleware's matrix for the `cottages` resource:
 * staff have no access; writes and reads are super_admin + admin only.
 */
class CottagePolicy
{
    private function isManager(User $user): bool
    {
        return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->isManager($user);
    }

    public function view(User $user, ?Cottage $cottage = null): bool
    {
        return $this->isManager($user);
    }

    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user, ?Cottage $cottage = null): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user, ?Cottage $cottage = null): bool
    {
        return $this->isManager($user);
    }
}

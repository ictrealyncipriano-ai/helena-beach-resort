<?php

namespace App\Policies;

use App\Models\Guest;
use App\Models\User;

/**
 * Authorization for the admin guests area.
 *
 * Mirrors AdminMiddleware's matrix for the `guests` resource:
 * staff have no access; reads and writes are super_admin + admin only.
 * Only abilities exposed by the routes are implemented (the resource
 * excludes create/store, so there is intentionally no create ability).
 */
class GuestPolicy
{
    private function isManager(User $user): bool
    {
        return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->isManager($user);
    }

    public function view(User $user, ?Guest $guest = null): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user, ?Guest $guest = null): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user, ?Guest $guest = null): bool
    {
        return $this->isManager($user);
    }
}

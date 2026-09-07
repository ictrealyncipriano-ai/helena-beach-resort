<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

/**
 * Authorization for the admin services area.
 *
 * Mirrors AdminMiddleware's matrix for the `services` resource:
 * staff have no access; reads and writes are super_admin + admin only.
 */
class ServicePolicy
{
    private function isManager(User $user): bool
    {
        return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->isManager($user);
    }

    public function view(User $user, ?Service $service = null): bool
    {
        return $this->isManager($user);
    }

    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user, ?Service $service = null): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user, ?Service $service = null): bool
    {
        return $this->isManager($user);
    }
}

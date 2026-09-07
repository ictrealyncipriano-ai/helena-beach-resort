<?php

namespace App\Policies;

use App\Models\SiteSetting;
use App\Models\User;

/**
 * Authorization for the admin site-settings area.
 *
 * Admin-read + super_admin-write split:
 *   - viewAny/view (index/edit form): super_admin, admin
 *   - create/update/delete (store/update/destroy): super_admin only
 * Staff have no access.
 */
class SiteSettingPolicy
{
    private function isReader(User $user): bool
    {
        return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true);
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->role === User::ROLE_SUPER_ADMIN;
    }

    public function viewAny(User $user): bool
    {
        return $this->isReader($user);
    }

    public function view(User $user, ?SiteSetting $siteSetting = null): bool
    {
        return $this->isReader($user);
    }

    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function update(User $user, ?SiteSetting $siteSetting = null): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function delete(User $user, ?SiteSetting $siteSetting = null): bool
    {
        return $this->isSuperAdmin($user);
    }
}

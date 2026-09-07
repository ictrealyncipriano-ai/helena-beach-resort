<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

/**
 * Authorization for the admin faqs area.
 *
 * Mirrors AdminMiddleware's matrix for the `faqs` resource:
 * staff have no access; reads, writes, and activate-all are
 * super_admin + admin only.
 */
class FaqPolicy
{
    private function isManager(User $user): bool
    {
        return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->isManager($user);
    }

    public function view(User $user, ?Faq $faq = null): bool
    {
        return $this->isManager($user);
    }

    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user, ?Faq $faq = null): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user, ?Faq $faq = null): bool
    {
        return $this->isManager($user);
    }

    public function activateAll(User $user): bool
    {
        return $this->isManager($user);
    }
}

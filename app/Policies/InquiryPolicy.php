<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;

/**
 * Authorization for the admin inquiries area.
 *
 * Mirrors AdminMiddleware's matrix for the `inquiries` resource so the
 * fragile route-name parsing can retire incrementally:
 *   - view (index/show/edit): super_admin, admin, staff
 *   - writes (store/update/destroy/confirm/cancel/markPaid/refund/
 *     approvePaymentProof/rejectPaymentProof): super_admin, admin
 * Staff keep read-only inquiry access; payment-proof approval is a write.
 */
class InquiryPolicy
{
    private function isStaff(User $user): bool
    {
        return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_STAFF], true);
    }

    private function isManager(User $user): bool
    {
        return in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, ?Inquiry $inquiry = null): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user, ?Inquiry $inquiry = null): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user, ?Inquiry $inquiry = null): bool
    {
        return $this->isManager($user);
    }

    public function confirm(User $user, ?Inquiry $inquiry = null): bool
    {
        return $this->isManager($user);
    }

    public function cancel(User $user, ?Inquiry $inquiry = null): bool
    {
        return $this->isManager($user);
    }

    public function markPaid(User $user, ?Inquiry $inquiry = null): bool
    {
        return $this->isManager($user);
    }

    public function refund(User $user, ?Inquiry $inquiry = null): bool
    {
        return $this->isManager($user);
    }

    public function approvePaymentProof(User $user, ?Inquiry $inquiry = null): bool
    {
        return $this->isManager($user);
    }

    public function rejectPaymentProof(User $user, ?Inquiry $inquiry = null): bool
    {
        return $this->isManager($user);
    }
}

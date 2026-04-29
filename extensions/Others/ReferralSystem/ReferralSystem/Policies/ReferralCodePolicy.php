<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Policies;

use App\Models\User;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCode;

class ReferralCodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.referrals.codes.view');
    }

    public function view(User $user, ReferralCode $code): bool
    {
        return $user->hasPermission('admin.referrals.codes.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.referrals.codes.manage');
    }

    public function update(User $user, ReferralCode $code): bool
    {
        return $user->hasPermission('admin.referrals.codes.manage');
    }

    public function delete(User $user, ReferralCode $code): bool
    {
        return $user->hasPermission('admin.referrals.codes.manage');
    }
}

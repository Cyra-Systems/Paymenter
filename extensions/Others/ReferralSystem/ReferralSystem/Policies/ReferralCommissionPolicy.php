<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Policies;

use App\Models\User;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralCommission;

class ReferralCommissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.referrals.commissions.view');
    }

    public function view(User $user, ReferralCommission $commission): bool
    {
        return $user->hasPermission('admin.referrals.commissions.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.referrals.commissions.manage');
    }

    public function update(User $user, ReferralCommission $commission): bool
    {
        return $user->hasPermission('admin.referrals.commissions.manage');
    }
}

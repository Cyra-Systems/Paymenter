<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Policies;

use App\Models\User;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralWithdrawal;

class ReferralWithdrawalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.referrals.withdrawals.manage');
    }

    public function view(User $user, ReferralWithdrawal $withdrawal): bool
    {
        return $user->hasPermission('admin.referrals.withdrawals.manage');
    }

    public function update(User $user, ReferralWithdrawal $withdrawal): bool
    {
        return $user->hasPermission('admin.referrals.withdrawals.manage');
    }
}

<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Policies;

use App\Models\User;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralManualCommissionSchedule;

class ReferralManualCommissionSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.referrals.commissions.view');
    }

    public function view(User $user, ReferralManualCommissionSchedule $schedule): bool
    {
        return $user->hasPermission('admin.referrals.commissions.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.referrals.commissions.manage');
    }

    public function update(User $user, ReferralManualCommissionSchedule $schedule): bool
    {
        return $user->hasPermission('admin.referrals.commissions.manage');
    }

    public function delete(User $user, ReferralManualCommissionSchedule $schedule): bool
    {
        return $user->hasPermission('admin.referrals.commissions.manage');
    }
}

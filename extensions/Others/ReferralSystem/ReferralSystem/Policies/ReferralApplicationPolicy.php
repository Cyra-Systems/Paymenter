<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Policies;

use App\Models\User;
use Paymenter\Extensions\Others\ReferralSystem\Models\ReferralApplication;

class ReferralApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.referrals.applications.view');
    }

    public function view(User $user, ReferralApplication $application): bool
    {
        return $user->hasPermission('admin.referrals.applications.view');
    }

    public function update(User $user, ReferralApplication $application): bool
    {
        return $user->hasPermission('admin.referrals.applications.update');
    }
}

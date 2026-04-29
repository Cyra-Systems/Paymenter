<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Console\Commands;

use Illuminate\Console\Command;
use Paymenter\Extensions\Others\ReferralSystem\Services\ManualCommissionManager;

class ProcessRecurringManualCommissions extends Command
{
    protected $signature = 'referrals:manual-commissions:process';

    protected $description = 'Process due recurring referral manual commission schedules';

    public function handle(): int
    {
        $processed = ManualCommissionManager::processDueSchedules();

        $this->info('Processed ' . $processed . ' recurring referral commission schedule(s).');

        return self::SUCCESS;
    }
}

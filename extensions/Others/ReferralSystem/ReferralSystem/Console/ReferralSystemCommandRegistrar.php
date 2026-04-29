<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Console;

use Illuminate\Console\Application as ArtisanApplication;
use Paymenter\Extensions\Others\ReferralSystem\Console\Commands\ProcessRecurringManualCommissions;

class ReferralSystemCommandRegistrar
{
    public static function register(): void
    {
        if (!app()->runningInConsole()) {
            return;
        }

        static $registered = false;

        if ($registered) {
            return;
        }

        $registered = true;

        $commands = [
            ProcessRecurringManualCommissions::class,
        ];

        foreach ($commands as $command) {
            app()->singleton($command);
        }

        ArtisanApplication::starting(function (ArtisanApplication $artisan) use ($commands) {
            $artisan->resolveCommands($commands);
        });
    }
}

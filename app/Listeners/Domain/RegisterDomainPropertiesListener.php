<?php

namespace App\Listeners\Domain;

use App\Events\Domain\Registered;

class RegisterDomainPropertiesListener
{
    public function handle(Registered $event): void
    {
        $domain = $event->domain;
        $registrarData = $domain->registrar_data ?? [];

        $nameservers = $registrarData['nameservers'] ?? [];
        if (is_array($nameservers)) {
            foreach (array_slice(array_values($nameservers), 0, 4) as $i => $ns) {
                $domain->properties()->updateOrCreate(
                    ['key' => 'nameserver_'.($i + 1)],
                    ['name' => 'Nameserver '.($i + 1), 'value' => (string) $ns],
                );
            }
        }

        if (! empty($domain->auth_code)) {
            $domain->properties()->updateOrCreate(
                ['key' => 'transfer_auth_code'],
                ['name' => 'Transfer Auth Code', 'value' => $domain->auth_code],
            );
        }

        if ($domain->expires_at) {
            $domain->properties()->updateOrCreate(
                ['key' => 'expires_at'],
                ['name' => 'Expires At', 'value' => $domain->expires_at->toIso8601String()],
            );
        }
    }
}

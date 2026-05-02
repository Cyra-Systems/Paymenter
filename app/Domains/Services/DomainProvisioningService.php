<?php

namespace App\Domains\Services;

use App\Domains\Registrars\EnomRegistrar;
use App\Domains\Registrars\RegistrarContract;
use App\Domains\Registrars\RegistrarFactory;
use App\Events\Domain\Registered;
use App\Events\Domain\Renewed;
use App\Events\Domain\TransferInitiated;
use App\Models\Domain;
use App\Models\DomainTld;
use App\Models\Service;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class DomainProvisioningService
{
    public function register(User $user, string $sld, string $tld, int $years, array $options = [], ?RegistrarContract $registrar = null): Domain
    {
        $sld = strtolower(trim($sld));
        $tld = strtolower(ltrim(trim($tld), '.'));
        $fqdn = $sld.'.'.$tld;

        $tldRecord = DomainTld::query()->where('tld', $tld)->where('enabled', true)->first();
        if (! $tldRecord) {
            throw new Exception('TLD .'.$tld.' is not enabled in the catalog.');
        }

        $years = max($tldRecord->min_years, min($tldRecord->max_years, $years));

        $registrar = $registrar ?? RegistrarFactory::byName('enom');

        return DB::transaction(function () use ($user, $sld, $tld, $fqdn, $years, $options, $tldRecord, $registrar) {
            $domain = Domain::create([
                'user_id' => $user->id,
                'sld' => $sld,
                'tld' => $tld,
                'fqdn' => $fqdn,
                'registrar' => 'enom',
                'status' => Domain::STATUS_PENDING,
                'auto_renew' => $options['auto_renew'] ?? true,
                'id_protect' => $options['id_protect'] ?? false,
                'currency_code' => $tldRecord->currency_code,
                'price' => $tldRecord->priceWithMargin('register_price'),
                'registered_via_service_id' => $options['service_id'] ?? null,
            ]);

            $contacts = $registrar instanceof EnomRegistrar
                ? $registrar->fullContactSet($user)
                : [];

            $result = $registrar->register($domain, $years, $contacts, $options);

            $info = $registrar->getInfo($domain);

            $domain->update([
                'status' => Domain::STATUS_ACTIVE,
                'registered_at' => now(),
                'expires_at' => $info['expires_at'] ?? now()->addYears($years),
                'auth_code' => $info['auth_code'] ?? null,
                'locked' => $info['locked'] ?? false,
                'last_synced_at' => now(),
                'registrar_data' => $result,
            ]);

            DB::afterCommit(function () use ($domain) {
                event(new Registered($domain->fresh()));
            });

            return $domain->fresh();
        });
    }

    public function registerForService(Service $service, array $settings, array $properties, ?RegistrarContract $registrar = null): Domain
    {
        $sld = strtolower(trim((string) ($properties['domain'] ?? '')));
        $tld = strtolower(ltrim(trim((string) ($settings['tld'] ?? '')), '.'));

        if ($sld === '' || $tld === '') {
            throw new Exception('Domain SLD or TLD is missing.');
        }

        return $this->register(
            $service->user,
            $sld,
            $tld,
            (int) ($settings['years'] ?? 1),
            [
                'id_protect' => ! empty($settings['id_protect']),
                'auto_renew' => ! empty($settings['auto_renew']),
                'lock' => ! empty($settings['lock']),
                'service_id' => $service->id,
            ],
            $registrar,
        );
    }

    public function renew(Domain $domain, int $years, ?RegistrarContract $registrar = null): Domain
    {
        $registrar = $registrar ?? RegistrarFactory::for($domain);
        $registrar->renew($domain, $years);

        $info = $registrar->getInfo($domain);

        $domain->update([
            'expires_at' => $info['expires_at'] ?? $domain->expires_at?->addYears($years),
            'last_synced_at' => now(),
        ]);

        DB::afterCommit(function () use ($domain, $years) {
            event(new Renewed($domain->fresh(), $years));
        });

        return $domain->fresh();
    }

    public function transfer(User $user, string $fqdn, string $authCode, ?RegistrarContract $registrar = null): Domain
    {
        $parts = explode('.', strtolower(trim($fqdn)), 2);
        if (count($parts) !== 2) {
            throw new Exception('Invalid FQDN: '.$fqdn);
        }

        [$sld, $tld] = $parts;

        $tldRecord = DomainTld::query()->where('tld', $tld)->where('enabled', true)->first();
        if (! $tldRecord || ! $tldRecord->transfer_supported) {
            throw new Exception('Transfer is not supported for .'.$tld);
        }

        $registrar = $registrar ?? RegistrarFactory::byName('enom');

        return DB::transaction(function () use ($user, $sld, $tld, $fqdn, $authCode, $tldRecord, $registrar) {
            $domain = Domain::create([
                'user_id' => $user->id,
                'sld' => $sld,
                'tld' => $tld,
                'fqdn' => $fqdn,
                'registrar' => 'enom',
                'status' => Domain::STATUS_TRANSFERRING,
                'auth_code' => $authCode,
                'currency_code' => $tldRecord->currency_code,
                'price' => $tldRecord->priceWithMargin('transfer_price'),
            ]);

            $registrar->transferIn($domain, $authCode);

            DB::afterCommit(function () use ($domain) {
                event(new TransferInitiated($domain->fresh()));
            });

            return $domain->fresh();
        });
    }
}

<?php

namespace App\Domains\Services;

use App\Events\Domain\BoundToService;
use App\Events\Domain\HostnameChanged;
use App\Events\Domain\UnboundFromService;
use App\Exceptions\DomainBindingConflict;
use App\Models\Domain;
use App\Models\DomainServiceBinding;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class DomainBindingService
{
    public function bind(Domain $domain, Service $service, string $type, string $hostname): DomainServiceBinding
    {
        return DB::transaction(function () use ($domain, $service, $type, $hostname) {
            $domain = Domain::query()->lockForUpdate()->findOrFail($domain->id);

            $current = $domain->bindings()
                ->whereIn('status', [DomainServiceBinding::STATUS_ACTIVE, DomainServiceBinding::STATUS_TRANSITIONING])
                ->lockForUpdate()
                ->first();

            if ($current && $current->service_id === $service->id && $current->hostname === $hostname) {
                return $current;
            }

            $blocker = DomainServiceBinding::query()
                ->where('service_id', $service->id)
                ->whereIn('status', [DomainServiceBinding::STATUS_ACTIVE, DomainServiceBinding::STATUS_TRANSITIONING])
                ->where('domain_id', '!=', $domain->id)
                ->lockForUpdate()
                ->first();

            if ($blocker) {
                throw new DomainBindingConflict(
                    'Service '.$service->id.' already has an active binding for a different domain.'
                );
            }

            $previousService = null;
            $previousHostname = null;

            if ($current) {
                $previousService = $current->service;
                $previousHostname = $current->hostname;

                $current->update([
                    'status' => DomainServiceBinding::STATUS_TRANSITIONING,
                    'transitioning' => true,
                ]);
            }

            $binding = DomainServiceBinding::create([
                'domain_id' => $domain->id,
                'service_id' => $service->id,
                'type' => $type,
                'hostname' => $hostname,
                'status' => DomainServiceBinding::STATUS_TRANSITIONING,
                'transitioning' => true,
                'bound_at' => now(),
            ]);

            DB::afterCommit(function () use ($domain, $service, $previousService, $type, $hostname, $previousHostname) {
                event(new BoundToService(
                    $domain->fresh(),
                    $service->fresh(),
                    $previousService?->fresh(),
                    $type,
                    $hostname,
                    $previousHostname,
                ));
            });

            return $binding;
        });
    }

    public function changeHostname(DomainServiceBinding $binding, string $newHostname): DomainServiceBinding
    {
        return DB::transaction(function () use ($binding, $newHostname) {
            $binding = DomainServiceBinding::query()->lockForUpdate()->findOrFail($binding->id);

            if ($binding->hostname === $newHostname) {
                return $binding;
            }

            $previousHostname = $binding->hostname;

            $binding->update([
                'hostname' => $newHostname,
                'status' => DomainServiceBinding::STATUS_TRANSITIONING,
                'transitioning' => true,
            ]);

            DB::afterCommit(function () use ($binding, $newHostname, $previousHostname) {
                event(new HostnameChanged(
                    $binding->domain,
                    $binding->service,
                    $newHostname,
                    $previousHostname,
                ));
            });

            return $binding;
        });
    }

    public function unbind(DomainServiceBinding $binding): void
    {
        DB::transaction(function () use ($binding) {
            $binding = DomainServiceBinding::query()->lockForUpdate()->findOrFail($binding->id);

            $binding->update([
                'status' => DomainServiceBinding::STATUS_RELEASED,
                'transitioning' => false,
                'released_at' => now(),
            ]);

            DB::afterCommit(function () use ($binding) {
                event(new UnboundFromService(
                    $binding->domain,
                    $binding->service,
                    $binding->hostname,
                ));
            });
        });
    }

    public function finalize(DomainServiceBinding $newBinding, ?DomainServiceBinding $oldBinding = null): void
    {
        DB::transaction(function () use ($newBinding, $oldBinding) {
            $newBinding->update([
                'status' => DomainServiceBinding::STATUS_ACTIVE,
                'transitioning' => false,
                'bound_at' => $newBinding->bound_at ?? now(),
            ]);

            if ($oldBinding) {
                $oldBinding->update([
                    'status' => DomainServiceBinding::STATUS_RELEASED,
                    'transitioning' => false,
                    'released_at' => now(),
                ]);
            }
        });
    }

    public function markFailed(DomainServiceBinding $binding, string $error): void
    {
        $binding->update([
            'status' => DomainServiceBinding::STATUS_FAILED,
            'transitioning' => false,
            'last_error' => $error,
        ]);
    }
}

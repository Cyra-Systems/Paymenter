<?php

namespace App\Livewire\Domains;

use App\Domains\Services\DomainBindingService;
use App\Domains\Services\SubdomainAllocator;
use App\Livewire\Component;
use App\Models\Domain;
use App\Models\Service;
use Exception;
use Illuminate\Support\Facades\Auth;

class Bind extends Component
{
    public Domain $domain;

    public ?int $targetServiceId = null;

    public string $hostname = '';

    public string $bindingType = Domain::TYPE_PRIMARY;

    public function mount(): void
    {
        $current = $this->domain->activeBinding;
        if ($current) {
            $this->targetServiceId = $current->service_id;
            $this->hostname = $current->hostname;
            $this->bindingType = $current->type;
        } else {
            $this->hostname = $this->domain->fqdn;
        }
    }

    public function eligibleServices()
    {
        return Service::query()
            ->where('user_id', Auth::id())
            ->where('status', Service::STATUS_ACTIVE)
            ->whereHas('product', fn ($q) => $q->where('requires_domain', true))
            ->with('product')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function bind(): void
    {
        $this->validate([
            'targetServiceId' => 'required|integer|exists:services,id',
            'hostname' => 'required|string|max:253',
            'bindingType' => 'required|in:primary,forward,subdomain,custom',
        ]);

        $service = Service::query()
            ->where('user_id', Auth::id())
            ->findOrFail($this->targetServiceId);

        if ($this->bindingType === Domain::TYPE_SUBDOMAIN) {
            $allocator = app(SubdomainAllocator::class);
            $prefix = explode('.', $this->hostname, 2)[0] ?? '';
            if (! $allocator->isAvailable($prefix)) {
                $this->notify('Subdomain prefix is not available.', 'error');

                return;
            }
        }

        try {
            app(DomainBindingService::class)->bind($this->domain, $service, $this->bindingType, $this->hostname);
            $this->notify('Domain rebinding initiated. Proxy and server hostname updates are queued.', 'success');
            $this->redirect(route('domains.show', $this->domain), true);
        } catch (Exception $e) {
            $this->notify('Bind failed: '.$e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('domains.bind', [
            'services' => $this->eligibleServices(),
        ])->layoutData([
            'title' => 'Bind '.$this->domain->fqdn,
            'sidebar' => true,
        ]);
    }
}

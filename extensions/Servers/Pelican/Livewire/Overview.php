<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;
use Illuminate\Support\Facades\Cache;

class Overview extends Component
{
    public ?array $panelServer     = null;
    public ?array $serverResources = null;
    public bool   $fastPolling     = false;

    public function mount(Service $service): void
    {
        parent::mount($service);
        $this->loadServer();
        $this->loadResources();
    }

    protected function loadServer(): void
    {
        $this->panelServer = $this->panelServer();
    }

    protected function loadResources(): void
    {
        if (! $this->panelServer) return;

        try {
            $oldState              = $this->serverResources['current_state'] ?? null;
            $this->serverResources = $this->pelican()->getServerResources($this->panelServer['uuid']);
            if ($oldState !== null && $oldState !== ($this->serverResources['current_state'] ?? null)) {
                $this->fastPolling = false;
            }
        } catch (Exception $e) {
            $this->serverResources = null;
        }
    }

    public function checkStatus(): void
    {
        if (! $this->panelServer) {
            $this->loadServer();
        }
        $this->loadResources();
    }

    public function render()
    {
        $eggId     = $this->eggId();
        $subdomain = null;
        $alloc     = null;
        $eggName   = null;

        if ($eggId > 0) {
            $cachedDomain = Cache::get('pelican_domain_' . $this->service->id . '_' . $eggId);
            $subdomain    = $cachedDomain ? 'https://' . $cachedDomain : null;
            $alloc        = Cache::get('pelican_alloc_' . $this->service->id . '_' . $eggId);

            try {
                $eggName = $this->pelican()->getEggName($eggId);
            } catch (Exception $e) {
                $eggName = 'Unknown';
            }
        }

        return view('pelican::tabs.overview', compact('subdomain', 'alloc', 'eggName'));
    }
}

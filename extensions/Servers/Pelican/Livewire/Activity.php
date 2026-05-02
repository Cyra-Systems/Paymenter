<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;

class Activity extends Component
{
    public ?array $panelServer = null;
    public array  $entries     = [];

    public function mount(Service $service): void
    {
        parent::mount($service);
        $this->requireToggle('show_activity');

        $this->panelServer = $this->panelServer();
        $this->refresh();
    }

    public function refresh(): void
    {
        if (! $this->panelServer) return;
        try {
            $resp = $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/activity'
            );
            $this->entries = array_map(fn($e) => $e['attributes'] ?? $e, $resp['data'] ?? []);
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('pelican::tabs.activity');
    }
}

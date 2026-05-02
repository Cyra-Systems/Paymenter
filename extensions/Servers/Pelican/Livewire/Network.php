<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;

class Network extends Component
{
    public ?array $panelServer = null;
    public array  $allocations = [];

    public function mount(Service $service): void
    {
        parent::mount($service);
        $this->requireToggle('show_network');

        $this->panelServer = $this->panelServer();
        $this->refresh();
    }

    public function refresh(): void
    {
        if (! $this->panelServer) return;
        try {
            $resp = $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/network/allocations'
            );
            $this->allocations = array_map(fn($a) => $a['attributes'] ?? $a, $resp['data'] ?? []);
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function setPrimary(int $id): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/network/allocations/' . $id . '/primary',
                'post'
            );
            $this->notify('Primary allocation updated.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function setNotes(int $id, string $notes): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/network/allocations/' . $id,
                'post',
                ['notes' => $notes]
            );
            $this->notify('Notes saved.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function createNew(): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/network/allocations',
                'post'
            );
            $this->notify('Allocation requested.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function delete(int $id): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/network/allocations/' . $id,
                'delete'
            );
            $this->notify('Allocation removed.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('pelican::tabs.network');
    }
}

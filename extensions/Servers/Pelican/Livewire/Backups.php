<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;

class Backups extends Component
{
    public ?array $panelServer = null;
    public array  $backups     = [];

    public function mount(Service $service): void
    {
        parent::mount($service);
        $this->requireToggle('show_backups');

        $this->panelServer = $this->panelServer();
        $this->refresh();
    }

    public function refresh(): void
    {
        if (! $this->panelServer) return;
        try {
            $resp = $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/backups'
            );
            $this->backups = array_map(fn($b) => $b['attributes'] ?? $b, $resp['data'] ?? []);
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function create(): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/backups',
                'post'
            );
            $this->notify('Backup queued.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function restore(string $uuid): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/backups/' . $uuid . '/restore',
                'post'
            );
            $this->notify('Restore started.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function delete(string $uuid): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/backups/' . $uuid,
                'delete'
            );
            $this->notify('Backup deleted.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('pelican::tabs.backups');
    }
}

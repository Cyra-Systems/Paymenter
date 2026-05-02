<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;

class Databases extends Component
{
    public ?array $panelServer = null;
    public array  $databases   = [];
    public bool   $creating    = false;
    public string $newName     = '';
    public string $newRemote   = '%';

    public function mount(Service $service): void
    {
        parent::mount($service);
        $this->requireToggle('show_databases');

        $this->panelServer = $this->panelServer();
        $this->refresh();
    }

    public function refresh(): void
    {
        if (! $this->panelServer) return;
        try {
            $resp = $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/databases',
                'get',
                ['include' => 'password']
            );
            $this->databases = array_map(fn($d) => $d['attributes'] ?? $d, $resp['data'] ?? []);
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function create(): void
    {
        if (! $this->panelServer || trim($this->newName) === '') return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/databases',
                'post',
                ['database' => $this->newName, 'remote' => $this->newRemote ?: '%']
            );
            $this->notify('Database created.', 'success');
            $this->newName = '';
            $this->creating = false;
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function rotate(string $id): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/databases/' . $id . '/rotate-password',
                'post'
            );
            $this->notify('Password rotated.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function delete(string $id): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/databases/' . $id,
                'delete'
            );
            $this->notify('Database deleted.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('pelican::tabs.databases');
    }
}

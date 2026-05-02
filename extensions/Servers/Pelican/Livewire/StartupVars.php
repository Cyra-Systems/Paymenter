<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;

class StartupVars extends Component
{
    public ?array $panelServer = null;
    public array  $variables   = [];
    public array  $values      = [];

    public function mount(Service $service): void
    {
        parent::mount($service);
        $this->requireToggle('show_startup_vars');

        $this->panelServer = $this->panelServer();
        $this->refresh();
    }

    public function refresh(): void
    {
        if (! $this->panelServer) return;
        try {
            $resp = $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/startup'
            );
            $this->variables = array_map(fn($v) => $v['attributes'] ?? $v, $resp['data'] ?? []);
            foreach ($this->variables as $v) {
                $this->values[$v['env_variable']] = $v['server_value'] ?? '';
            }
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function save(string $key): void
    {
        if (! $this->panelServer || ! array_key_exists($key, $this->values)) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/startup/variable',
                'put',
                ['key' => $key, 'value' => (string) $this->values[$key]]
            );
            $this->notify($key . ' updated.', 'success');
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('pelican::tabs.startup-vars');
    }
}

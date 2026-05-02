<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;

class Console extends Component
{
    public ?array $panelServer     = null;
    public ?array $serverResources = null;
    public bool   $fastPolling     = false;
    public string $command         = '';

    public function mount(Service $service): void
    {
        parent::mount($service);
        $this->requireToggle('show_console');

        $this->panelServer = $this->panelServer();
        $this->loadResources();
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
        $this->loadResources();
    }

    public function power(string $signal): void
    {
        if (! $this->panelServer) return;

        if (! in_array($signal, ['start', 'stop', 'restart', 'kill'], true)) {
            $this->notify('Invalid power signal.', 'error');
            return;
        }

        try {
            $this->pelican()->powerServer($this->panelServer['uuid'], $signal);
            $this->notify('Power signal sent: ' . $signal, 'success');
            $this->fastPolling = true;
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function send(): void
    {
        $settings = $this->productSettings();
        if (empty($settings['show_send_command'])) {
            $this->notify('Sending commands is not allowed for this plan.', 'error');
            return;
        }

        $cmd = trim($this->command);
        if ($cmd === '' || ! $this->panelServer) return;

        try {
            $this->pelican()->sendCommand($this->panelServer['uuid'], $cmd);
            $this->notify('Command sent.', 'success');
            $this->command = '';
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('pelican::tabs.console', [
            'settings' => $this->productSettings(),
        ]);
    }
}

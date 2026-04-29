<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;

class Console extends Component
{
    public ?array $panelServer     = null;
    public ?array $serverResources = null;
    public bool   $fastPolling     = false;

    public function mount(Service $service): void
    {
        parent::mount($service);

        $settings = $this->productSettings();
        if (empty($settings['show_console'])) {
            abort(403);
        }

        $this->loadServer();
        $this->loadResources();
    }

    protected function loadServer(): void
    {
        $eggId = $this->eggId();
        if ($eggId <= 0) {
            return;
        }

        try {
            $this->panelServer = $this->pelican()->getServer($this->service->id, $eggId);
        } catch (Exception $e) {
            $this->panelServer = null;
        }
    }

    protected function loadResources(): void
    {
        if (! $this->panelServer) {
            return;
        }

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
        if (! $this->panelServer) {
            return;
        }

        if (! in_array($signal, ['start', 'stop', 'restart', 'kill'], true)) {
            $this->notify('Invalid power signal.', 'error');
            return;
        }

        try {
            $this->pelican()->powerServer($this->panelServer['uuid'], $signal);
            $this->notify('Power signal sent.', 'success');
            $this->fastPolling = true;
        } catch (Exception $e) {
            $this->notify('Failed: ' . $e->getMessage(), 'error');
        }
    }

    public function reinstall(): void
    {
        $settings = $this->productSettings();

        if (empty($settings['show_reinstall'])) {
            $this->notify('Reinstall is not allowed for this plan.', 'error');
            return;
        }

        if (! $this->panelServer) {
            return;
        }

        try {
            $this->pelican()->reinstallPanelServer($this->panelServer['id']);
            $this->notify('Reinstall initiated.', 'success');
        } catch (Exception $e) {
            $this->notify('Failed: ' . $e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('pelican::console', [
            'settings' => $this->productSettings(),
        ])->layoutData(['sidebar' => true]);
    }
}

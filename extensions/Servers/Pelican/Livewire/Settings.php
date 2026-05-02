<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;

class Settings extends Component
{
    public ?array $panelServer = null;
    public string $newName     = '';

    public function mount(Service $service): void
    {
        parent::mount($service);
        $this->requireToggle('show_settings');

        $this->panelServer = $this->panelServer();
        if ($this->panelServer) {
            $this->newName = $this->panelServer['name'] ?? '';
        }
    }

    public function rename(): void
    {
        if (! $this->panelServer || trim($this->newName) === '') return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/settings/rename',
                'post',
                ['name' => $this->newName]
            );
            $this->notify('Server renamed.', 'success');
            $this->panelServer['name'] = $this->newName;
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function regenerateSftp(): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/settings/regenerate-sftp-password',
                'post'
            );
            $this->notify('SFTP password regenerated.', 'success');
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function reinstall(): void
    {
        $settings = $this->productSettings();
        if (empty($settings['show_reinstall'])) {
            $this->notify('Reinstall is not allowed for this plan.', 'error');
            return;
        }
        if (! $this->panelServer) return;
        try {
            $this->pelican()->reinstallPanelServer((int) $this->panelServer['id']);
            $this->notify('Reinstall initiated.', 'success');
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('pelican::tabs.settings', [
            'settings' => $this->productSettings(),
        ]);
    }
}

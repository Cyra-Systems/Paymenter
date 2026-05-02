<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;

class Subusers extends Component
{
    public ?array $panelServer = null;
    public array  $subusers    = [];
    public string $newEmail    = '';
    /** @var string[] */
    public array  $newPermissions = ['control.console'];

    public const PERMISSIONS = [
        'control.console'    => 'Send commands & view console',
        'control.start'      => 'Start the server',
        'control.stop'       => 'Stop the server',
        'control.restart'    => 'Restart the server',
        'user.create'        => 'Create subusers',
        'user.read'          => 'List subusers',
        'user.update'        => 'Update subusers',
        'user.delete'        => 'Delete subusers',
        'file.create'        => 'Create files',
        'file.read'          => 'Read files',
        'file.read-content'  => 'Read file content',
        'file.update'        => 'Modify files',
        'file.delete'        => 'Delete files',
        'file.archive'       => 'Compress / decompress files',
        'file.sftp'          => 'Use SFTP',
        'backup.create'      => 'Create backups',
        'backup.read'        => 'View backups',
        'backup.delete'      => 'Delete backups',
        'backup.download'    => 'Download backups',
        'backup.restore'     => 'Restore backups',
        'allocation.read'    => 'View allocations',
        'allocation.create'  => 'Create allocations',
        'allocation.update'  => 'Update allocations',
        'allocation.delete'  => 'Delete allocations',
        'startup.read'       => 'View startup config',
        'startup.update'     => 'Update startup config',
        'startup.docker-image' => 'Change docker image',
        'database.create'    => 'Create databases',
        'database.read'      => 'View databases',
        'database.update'    => 'Update databases',
        'database.delete'    => 'Delete databases',
        'database.view_password' => 'View database password',
        'schedule.create'    => 'Create schedules',
        'schedule.read'      => 'View schedules',
        'schedule.update'    => 'Update schedules',
        'schedule.delete'    => 'Delete schedules',
        'settings.rename'    => 'Rename server',
        'settings.reinstall' => 'Reinstall server',
        'activity.read'      => 'View activity log',
    ];

    public function mount(Service $service): void
    {
        parent::mount($service);
        $this->requireToggle('show_subusers');

        $this->panelServer = $this->panelServer();
        $this->refresh();
    }

    public function refresh(): void
    {
        if (! $this->panelServer) return;
        try {
            $resp = $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/users'
            );
            $this->subusers = array_map(fn($u) => $u['attributes'] ?? $u, $resp['data'] ?? []);
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function add(): void
    {
        if (! $this->panelServer || trim($this->newEmail) === '' || empty($this->newPermissions)) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/users',
                'post',
                ['email' => $this->newEmail, 'permissions' => array_values($this->newPermissions)]
            );
            $this->newEmail = '';
            $this->notify('Subuser added.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function remove(string $uuid): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/users/' . $uuid,
                'delete'
            );
            $this->notify('Subuser removed.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('pelican::tabs.subusers', ['allPermissions' => self::PERMISSIONS]);
    }
}

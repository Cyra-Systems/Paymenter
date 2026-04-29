<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire\Backups;

use Paymenter\Extensions\Servers\Proxmox\Livewire\Component;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class Create extends Component
{
    public $visible;

    public $name;

    public $type = 'snapshot';

    public $compression = 'zstd';

    public function backup()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:snapshot,suspend,stop',
            'compression' => 'required|in:gzip,lzo,zstd,none',
        ]);
        // Validate if backups are allowed
        $backupLimit = $this->server->setting('backups');
        if ($backupLimit <= 0) {
            $this->notify('Backups are not enabled for this server', 'error');
            return;
        }
        // Validate if current backups are less than limit
        $proxmox = new Proxmox;
        $currentBackups = $proxmox->request('/nodes/' . $this->server->node->name . '/storage/' . $this->server->node->backup_location . '/content', data: [
            'content' => 'backup',
            'vmid' => $this->server->vm_id,
        ], location: $this->server->node->location)->json()['data'];

        if (count($currentBackups) >= $backupLimit) {
            $this->notify('You have reached the backup limit for this server', 'error');
            return;
        }

        $proxmox->request('/nodes/' . $this->server->node->name . '/vzdump', 'post', data: [
            'vmid' => $this->server->vm_id,
            'storage' => $this->server->node->backup_location,
            'mode' => $this->type,
            'compress' => $this->compression == 'none' ? (int) false : $this->compression,
            'notes-template' => $this->name,
        ], location: $this->server->node->location);

        $this->notify('Backup started', 'success');

        $this->visible = false;

        $this->reset([
            'name',
            'type',
            'compression',
        ]);
    }

    public function render()
    {
        return view('proxmox::backups.create');
    }
}

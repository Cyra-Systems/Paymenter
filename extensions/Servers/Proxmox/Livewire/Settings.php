<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire;

use App\Helpers\NotificationHelper;
use App\Rules\Domain;
use Illuminate\Support\Str;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class Settings extends Component
{
    public $hostname = '';

    public function mount(Server $server)
    {
        parent::mount($server);
        $this->hostname = $server->hostname;
    }

    public function updateHostname()
    {
        $this->validate([
            'hostname' => [
                'required',
                'string',
                new Domain()
            ]
        ]);
        if ($this->hostname === $this->server->hostname) {
            $this->notify('Hostname is unchanged.', 'info');
            return;
        }
        // Update proxmox hostname 
        $proxmox = new Proxmox();
        $proxmox->request('/nodes/' . $this->server->node->name . '/qemu/' . $this->server->vm_id . '/config', method: 'PUT', data: [
            'name' => $this->server->hostname,
            'searchdomain' => $this->server->hostname,
        ], location: $this->server->node->location);

        $this->server->update([
            'hostname' => $this->hostname,
        ]);

        $this->notify('Hostname updated successfully.', 'success');
    }

    public function requestPasswordReset()
    {
        try {
            $newPassword = Str::password(16, symbols: false);
            $proxmox = new Proxmox();

            $os = $proxmox->request('/nodes/' . $this->server->node->name . '/qemu/' . $this->server->vm_id . '/agent/get-osinfo', location: $this->server->node->location)->json();
            if (Str::contains(strtolower($os['data']['result']['name']), 'windows')) {
                $username = 'Administrator';
            } else {
                $username = 'root';
            }

            $proxmox->request('/nodes/' . $this->server->node->name . '/qemu/' . $this->server->vm_id . '/agent/set-user-password', method: 'POST', data: [
                'username' => $username,
                'password' => $newPassword,
            ], location: $this->server->node->location);

            $this->notify('Password reset successfully.', 'success');

            NotificationHelper::serverCreatedNotification($this->server->service->user, $this->server->service, [
                'password' => $newPassword,
                'ip' => $this->server->primaryIpv4 ? $this->server->primaryIpv4->ip : null,
                'ipv6' => $this->server->primaryIpv6 ? $this->server->primaryIpv6->ip : null,
                'hostname' => $this->server->hostname,
            ]);
        } catch (\Exception $e) {
            $this->notify('Failed to reset password', 'error');
        }


    }

    public function render()
    {
        return view('proxmox::settings')->layoutData([
            'sidebar' => true,
        ]);
    }
}

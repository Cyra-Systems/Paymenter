<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire;

use Paymenter\Extensions\Servers\Proxmox\Models\Server as ServerModel;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class Server extends Component
{
    public $status;

    public function mount(ServerModel $server)
    {
        parent::mount($server);

        $proxmox = new Proxmox;
        $this->status = (object) $proxmox->request('/nodes/' . $this->server->node->name . '/qemu/' . $this->server->vm_id . '/status/current', location: $this->server->node->location)->json()['data'];
    }

    public function render()
    {
        return view('proxmox::server');
    }

    public function placeholder(array $params = [])
    {
        return view('proxmox::serverplaceholder', $params);
    }
}

<?php

namespace Paymenter\Extensions\Servers\Proxmox\Livewire;

use App\Livewire\Component as BaseComponent;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;

class Component extends BaseComponent
{
    /**
     * The server instance.
     */
    public Server $server;

    /**
     * Mount the component with the server. 361be62a769f847e6dea837bca39444e
     *
     * @return void
     */
    public function mount(Server $server)
    {
        $this->server = $server;
        if ($server->user_id != auth()->id()) {
            abort(403);
        }
    }
}

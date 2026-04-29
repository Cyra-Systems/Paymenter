<?php

namespace Paymenter\Extensions\Servers\Proxmox\Jobs;

use App\Helpers\ExtensionHelper;
use App\Helpers\NotificationHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Paymenter\Extensions\Servers\Proxmox\Models\Server;
use Paymenter\Extensions\Servers\Proxmox\Proxmox;

class ReinstallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    // Timeout
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(public Server $server) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $proxmox = new Proxmox;
        // Stop server
        try {
            $proxmox->requestAndWait('/nodes/' . $this->server->node->name . '/qemu/' . $this->server->vm_id . '/status/stop', 'post', [
                'vmid' => $this->server->vm_id,
            ], $this->server->node->name, $this->server->node->location);

            // Delete proxmox server
            $proxmox->requestAndWait('/nodes/' . $this->server->node->name . '/qemu/' . $this->server->vm_id, 'delete', [], $this->server->node->name, location: $this->server->node->location);
        } catch (Exception $e) {
            if (!Str::contains($e->getMessage(), 'does not exist')) {
                throw $e;
            }
        }

        $password = Str::password(16);

        // Create the server

        $settings = array_merge(ExtensionHelper::settingsToArray($this->server->service->product->settings), ExtensionHelper::getServiceProperties($this->server->service));

        // Create the server
        $proxmox->install($this->server, $settings, $password);

        NotificationHelper::serverCreatedNotification($this->server->service->user, $this->server->service, [
            'password' => $password,
            'ip' => $this->server->primaryIpv4 ? $this->server->primaryIpv4->ip : null,
            'ipv6' => $this->server->primaryIpv6 ? $this->server->primaryIpv6->ip : null,
            'hostname' => $this->server->hostname,
        ]);
    }
}

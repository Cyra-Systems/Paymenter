<?php

namespace Paymenter\Extensions\Servers\Proxmox\Models;

use Illuminate\Database\Eloquent\Model;

class IPPool extends Model
{
    protected $table = 'ext_proxmox_ip_pools';

    protected $fillable = [
        // Shows to customer
        'name',
        'network_interface',
        'mtu',
        'vlan',
        'firewall',
    ];

    public function nodes()
    {
        return $this->belongsToMany(Node::class, 'ext_proxmox_ip_pool_nodes', 'ip_pool_id', 'node_id');
    }

    public function ipAddresses()
    {
        return $this->hasMany(IPAddress::class, 'ip_pool_id', 'id');
    }
}

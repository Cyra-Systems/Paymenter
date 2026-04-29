<?php

namespace Paymenter\Extensions\Servers\Proxmox\Models;

use App\Models\Service;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class Server extends Model
{
    protected $table = 'ext_proxmox_servers';

    protected $fillable = [
        'user_id',
        'node_id',
        'os_id',
        'service_id',
        'vm_id',
        'primary_ipv4',
        'primary_ipv6',
        'status',
        'hostname',
        'bandwidth_usage',
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function os()
    {
        return $this->belongsTo(OS::class);
    }

    public function ipAddresses()
    {
        return $this->hasMany(IPAddress::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function primaryIpv4()
    {
        return $this->belongsTo(IPAddress::class, 'primary_ipv4');
    }

    public function primaryIpv6()
    {
        return $this->belongsTo(IPAddress::class, 'primary_ipv6');
    }

    public function setting($settingKey)
    {
        // Read first from properties of the linked services, then from product settings
        if (
            $configOption = $this->service->configs()->whereHas('configOption', function ($query) use ($settingKey) {
                $query->where('env_variable', $settingKey);
            })->first()
        ) {
            return $configOption->value;
        } elseif ($this->service->properties->where('key', $settingKey)->first()) {
            return $this->service->properties->where('key', $settingKey)->first()->value;
        } else {
            $productSetting = $this->service->product->settings->where('key', $settingKey)->first();
            return $productSetting ? $productSetting->value : null;
        }
    }

    public function bandwidthLimit(): Attribute
    {
        return new Attribute(
            get: function () {
                if ($this->setting('bandwidth_limit')) {
                    return $this->setting('bandwidth_limit') * 1024 * 1024 * 1024;
                } else {
                    return null;
                }
            }
        );
    }

    public function bandwidthUsagePercentage(): Attribute
    {
        return new Attribute(
            get: function () {
                if ($this->bandwidthLimit) {
                    return ($this->bandwidth_usage / $this->bandwidthLimit) * 100;
                } else {
                    return null;
                }
            }
        );
    }

    public function isOverBandwidthLimit(): bool
    {
        if ($this->bandwidthLimit && $this->bandwidth_usage >= $this->bandwidthLimit) {
            return true;
        }
        return false;
    }
}

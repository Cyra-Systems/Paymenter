<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DomainDnsRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'hostname',
        'type',
        'value',
        'priority',
        'ttl',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
        'priority' => 'integer',
        'ttl' => 'integer',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}

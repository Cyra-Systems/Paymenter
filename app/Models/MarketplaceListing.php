<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketplaceListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'version',
        'author',
        'description',
        'icon',
        'download_url',
        'sha256',
        'signature',
        'has_migrations',
        'synced_at',
        'raw_meta',
    ];

    protected $casts = [
        'has_migrations' => 'boolean',
        'synced_at' => 'datetime',
        'raw_meta' => 'array',
    ];
}

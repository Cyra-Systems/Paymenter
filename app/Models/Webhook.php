<?php

namespace App\Models;

class Webhook extends Model
{
    protected $fillable = [
        'user_id',
        'url',
        'secret',
        'events',
        'enabled',
        'last_called_at',
    ];

    protected $casts = [
        'events' => 'array',
        'enabled' => 'boolean',
        'last_called_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

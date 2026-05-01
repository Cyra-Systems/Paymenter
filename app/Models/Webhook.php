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
        'last_response_status',
    ];

    protected $casts = [
        'events'               => 'array',
        'enabled'              => 'boolean',
        'last_called_at'       => 'datetime',
        'last_response_status' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DomainSetting extends Model
{
    use HasFactory;

    protected $table = 'domain_settings';

    protected $fillable = ['key', 'value', 'encrypted'];

    protected $casts = ['encrypted' => 'boolean'];
}

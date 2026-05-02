<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable;

class Theme extends Model implements Auditable
{
    use HasFactory, Traits\Auditable;

    protected $fillable = [
        'name',
        'version',
        'author',
        'active',
        'sha256',
        'signature',
        'source_url',
        'installed_version',
        'last_built_at',
        'last_build_status',
        'last_build_log_path',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_built_at' => 'datetime',
    ];

    public function path(): Attribute
    {
        return Attribute::make(
            get: fn () => base_path('themes/' . $this->name)
        );
    }

    public function setActive(): void
    {
        DB::transaction(function () {
            static::where('id', '!=', $this->id)->update(['active' => false]);
            $this->update(['active' => true]);

            Setting::updateOrCreate(
                ['key' => 'theme', 'settingable_type' => null],
                ['value' => $this->name, 'type' => 'string', 'encrypted' => false]
            );
        });
    }
}

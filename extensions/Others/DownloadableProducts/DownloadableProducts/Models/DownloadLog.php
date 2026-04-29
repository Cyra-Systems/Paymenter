<?php
namespace Paymenter\Extensions\Servers\DownloadableProducts\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadLog extends Model
{
    protected $fillable = [
        'service_id',
        'file_name',
        'user_id',
        'ip_address',
    ];

    public function service()
    {
        return $this->belongsTo(\App\Models\Service::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

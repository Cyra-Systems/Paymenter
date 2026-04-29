<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Models;

use App\Models\Model;
use App\Models\Order;

class ReferralOrder extends Model
{
    protected $table = 'ext_referral_orders';

    protected $fillable = [
        'order_id',
        'referral_code_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }
}

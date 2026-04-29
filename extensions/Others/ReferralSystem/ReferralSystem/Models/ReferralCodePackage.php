<?php

namespace Paymenter\Extensions\Others\ReferralSystem\Models;

use App\Models\Model;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReferralCodePackage extends Model
{
    use HasFactory;

    protected $table = 'ext_referral_code_packages';

    protected $fillable = [
        'referral_code_id',
        'product_id',
        'revenue_share',
        'purchase_limit',
        'purchases_count',
    ];

    protected $casts = [
        'revenue_share' => 'decimal:2',
        'purchase_limit' => 'integer',
        'purchases_count' => 'integer',
    ];

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

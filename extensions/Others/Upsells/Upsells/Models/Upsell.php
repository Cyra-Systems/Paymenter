<?php

namespace Paymenter\Extensions\Others\Upsells\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Upsell extends Model
{
    protected $table = 'ext_upsells';

    protected $fillable = [
        'type',
        'template',
        'trigger_product_id',
        'target_product_id',
        'minimum_spend',
        'allow_multiple_claims',
        'max_claims',
        'title',
        'description',
        'unlocked_title',
        'unlocked_description',
        'label',
        'show_price_difference',
        'auto_add_to_cart',
        'preconfigured_options',
        'enabled',
        'priority',
    ];

    protected $casts = [
        'show_price_difference' => 'boolean',
        'auto_add_to_cart' => 'boolean',
        'preconfigured_options' => 'array',
        'enabled' => 'boolean',
        'priority' => 'integer',
        'minimum_spend' => 'decimal:2',
        'allow_multiple_claims' => 'boolean',
        'max_claims' => 'integer',
    ];

    public function triggerProduct()
    {
        return $this->belongsTo(Product::class, 'trigger_product_id');
    }

    public function targetProduct()
    {
        return $this->belongsTo(Product::class, 'target_product_id');
    }
}


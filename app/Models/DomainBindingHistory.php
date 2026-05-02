<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DomainBindingHistory extends Model
{
    use HasFactory;

    protected $table = 'domain_binding_history';

    protected $fillable = [
        'domain_id', 'previous_bindable_id', 'previous_bindable_type',
        'new_bindable_id', 'new_bindable_type', 'old_hostname', 'new_hostname',
        'user_id', 'reason',
    ];

    public function domain(): BelongsTo { return $this->belongsTo(Domain::class); }
    public function previousBindable(): MorphTo { return $this->morphTo('previous_bindable'); }
    public function newBindable(): MorphTo { return $this->morphTo('new_bindable'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

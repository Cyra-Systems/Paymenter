<?php

namespace Paymenter\Extensions\Others\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostFeedback extends Model
{
    protected $table = 'ext_blog_post_feedback';

    protected $fillable = [
        'blog_post_id',
        'user_id',
        'is_helpful',
        'reasons',
        'note',
        'session_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
        'reasons' => 'array',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function scopeHelpful(Builder $query): Builder
    {
        return $query->where('is_helpful', true);
    }

    public function scopeUnhelpful(Builder $query): Builder
    {
        return $query->where('is_helpful', false);
    }
}

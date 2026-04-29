<?php

namespace Paymenter\Extensions\Others\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostView extends Model
{
    protected $table = 'ext_blog_post_views';

    protected $fillable = [
        'blog_post_id',
        'session_id',
        'ip_address',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }
}

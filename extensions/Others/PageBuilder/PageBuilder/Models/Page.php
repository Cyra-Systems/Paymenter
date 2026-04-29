<?php

namespace Paymenter\Extensions\Others\PageBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Page extends Model
{
    protected $table = 'ext_page_builder_pages';

    protected $fillable = [
        'route',
        'show_in_nav',
        'show_in_dropdown',
        'dropdown_name',
        'nav_priority',
        'title',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_og_title',
        'meta_og_description',
        'meta_og_image',
        'meta_twitter_title',
        'meta_twitter_description',
        'meta_twitter_image',
        'meta_twitter_card',
        'canonical_url',
        'sections'
    ];

    protected $casts = [
        'show_in_nav' => 'boolean',
        'sections' => 'array',
    ];

    public function setMetaOgImageAttribute($value): void
    {
        $this->attributes['meta_og_image'] = $this->ensureAbsoluteUrl($value);
    }

    public function setMetaTwitterImageAttribute($value): void
    {
        $this->attributes['meta_twitter_image'] = $this->ensureAbsoluteUrl($value);
    }

    private function ensureAbsoluteUrl($value): ?string
    {
        if (!$value) {
            return null;
        }
        $value = (string) $value;
        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }
        $path = Storage::url($value);
        return url($path);
    }
}

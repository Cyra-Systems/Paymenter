<?php

namespace Paymenter\Extensions\Others\Upsells\Models;

use Illuminate\Database\Eloquent\Model;

class UpsellSettings extends Model
{
    protected $table = 'ext_upsells_settings';

    protected $fillable = [
        'template',
        'use_custom',
        'position',
        'layout',
        'primary_color',
        'background_color',
        'border_color',
        'text_color',
        'button_style',
        'border_radius',
        'padding',
        'margin',
        'show_label',
        'show_description',
        'show_icon',
        'animation',
        'preview_background',
        'preview_card_background',
        'preview_text',
        'preview_muted',
        'preview_border',
    ];

    protected $casts = [
        'use_custom' => 'boolean',
        'border_radius' => 'integer',
        'padding' => 'integer',
        'margin' => 'integer',
    ];

    public function getShowIconAttribute($value)
    {
        $raw = $this->attributes['show_icon'] ?? null;
        if ($raw === null) {
            return true;
        }
        return (bool) $raw;
    }
    
    public function getShowLabelAttribute($value)
    {
        $raw = $this->attributes['show_label'] ?? null;
        if ($raw === null) {
            return true;
        }
        return (bool) $raw;
    }
    
    public function getShowDescriptionAttribute($value)
    {
        $raw = $this->attributes['show_description'] ?? null;
        if ($raw === null) {
            return true;
        }
        return (bool) $raw;
    }

    public static function get()
    {
        return static::first() ?? new static();
    }
}


<?php

namespace Paymenter\Extensions\Others\CustomCSS;

use App\Classes\Extension\Extension;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\HtmlString;

class CustomCSS extends Extension
{
    public function getConfig($values = [])
    {
        return [
            [
                'name' => 'css',
                'label' => 'Custom CSS',
                'type' => 'textarea',
                'default' => '',
                'rows' => 10,
            ],
        ];
    }

    public function boot()
    {
        Event::listen('head', function () {
            $css = trim((string) ($this->config('css') ?? ''));
            if ($css === '') {
                return null;
            }
            $styles = '<style>' . $css . '</style>';
            return ['view' => new HtmlString($styles)];
        });
    }
}



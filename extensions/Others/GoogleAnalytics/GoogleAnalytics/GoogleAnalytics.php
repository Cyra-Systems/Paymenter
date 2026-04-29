<?php

namespace Paymenter\Extensions\Others\GoogleAnalytics;

use App\Classes\Extension\Extension;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\HtmlString;

class GoogleAnalytics extends Extension 
{
    /**
     * Get all the configuration for the extension
     * 
     * @param array $values
     * @return array
     */
    public function getConfig($values = [])
    {
        return [
            [
                'name' => 'tracking_id',
                'label' => 'Google Analytics Measurement ID',
                'type' => 'text',
                'description' => 'Example: G-XXXXXXX',
                'required' => true,
                'validation' => 'string',
            ],
        ];
    }

    public function boot()
    {
        Event::listen('head', function () {
            $measurementId = trim((string) $this->config('tracking_id'));
            if ($measurementId === '') {
                return;
            }

            $id = e($measurementId);
            $head = '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $id . '"></script>';
            $head .= "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js', new Date());gtag('config','" . $id . "');</script>";

            return ['view' => new HtmlString($head)];
        });
    }
}
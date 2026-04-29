<?php

namespace Paymenter\Extensions\Others\CloudflareAnalytics;

use App\Attributes\ExtensionMeta;
use App\Classes\Extension\Extension;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;

#[ExtensionMeta(
    name: 'Cloudflare Web Analytics',
    description: 'Integrate Cloudflare Web Analytics to track visitor metrics, page views, and performance data across your Paymenter installation.',
    version: '1.0.0',
    author: 'Onyx',
    url: 'https://builtbybit.com/members/onyxdigital.600543/'
)]
class CloudflareAnalytics extends Extension
{
    public function getConfig($values = [])
    {
        return [
            [
                'name' => 'beacon_token',
                'label' => 'Cloudflare Web Analytics Beacon Token',
                'type' => 'text',
                'placeholder' => 'Enter your Beacon Token',
                'description' => 'Get your Beacon Token from Cloudflare Dashboard → Analytics & Logs → Web Analytics. It looks like: abc123def456...',
                'required' => true,
            ],
        ];
    }

    public function boot()
    {
        // Register view namespace
        View::addNamespace('cloudflare-analytics', __DIR__ . '/resources/views');

        // Inject analytics script into all pages
        Event::listen('head', function () {
            $beaconToken = $this->config('beacon_token');

            if (empty($beaconToken)) {
                return null;
            }

            return [
                'view' => view('cloudflare-analytics::script', [
                    'beaconToken' => $beaconToken,
                ])->render(),
            ];
        });
    }
}

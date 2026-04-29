<?php

namespace Paymenter\Extensions\Others\GoogleTagManager;

use App\Classes\Extension\Extension;
use App\Helpers\ExtensionHelper;
use Illuminate\Support\HtmlString;
use Exception;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;

class GoogleTagManager extends Extension 
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
                    'name' => 'gtm_container_id',
                    'label' => 'Container ID',
                    'description' => 'Google Tag Manager Container ID',
                    'type' => 'text',
                    'required' => true,
                ]
            ];

    }

    public function boot()
    {
       
        // Register views
        View::addNamespace('GTM_Paymenter', __DIR__ . '/resources/views');
      
        // Event listeners
        Event::listen('head', function () {
            return [
                'view' => view('GTM_Paymenter::head',[
                    'gtmContainerId' => ExtensionHelper::getExtension('other', 'GoogleTagManager')?->config('gtm_container_id')
                ])
            ];
        });

        Event::listen('body', function () {
            return [
                'view' => view('GTM_Paymenter::body',[
                    'gtmContainerId' => ExtensionHelper::getExtension('other', 'GoogleTagManager')?->config('gtm_container_id')
                ])
            ];
        });
        

    }
}
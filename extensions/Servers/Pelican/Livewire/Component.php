<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Helpers\ExtensionHelper;
use App\Livewire\Component as BaseComponent;
use App\Models\Service;
use Paymenter\Extensions\Servers\Pelican\Pelican;

class Component extends BaseComponent
{
    public Service $service;

    public function mount(Service $service): void
    {
        $this->service = $service->load('product.server');

        if ($service->user_id !== auth()->id()) {
            abort(403);
        }

        if (optional($this->service->product->server)->extension !== 'Pelican') {
            abort(404);
        }
    }

    protected function pelican(): Pelican
    {
        $settings = ExtensionHelper::settingsToArray($this->service->product->server->settings);
        return new Pelican($settings);
    }

    protected function productSettings(): array
    {
        return ExtensionHelper::settingsToArray($this->service->product->settings);
    }

    protected function eggId(): int
    {
        $props = ExtensionHelper::getServiceProperties($this->service);
        return (int) ($props['selected_egg'] ?? 0);
    }
}

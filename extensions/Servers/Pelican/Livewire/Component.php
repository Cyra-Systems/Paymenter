<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Helpers\ExtensionHelper;
use App\Livewire\Component as BaseComponent;
use App\Models\Service;
use Paymenter\Extensions\Servers\Pelican\Pelican;

abstract class Component extends BaseComponent
{
    public Service $service;

    /**
     * Mounted via `<livewire:pelican.* :service="$service" />` from the
     * Service Show tab renderer. The parent page already enforces
     * `can:view,service` so we only re-check the extension binding.
     */
    public function mount(Service $service): void
    {
        $this->service = $service->load('product.server');

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

    /**
     * Resolves the Pelican panel server attributes (id, uuid, identifier, …) for
     * this service's currently-selected egg. Returns null if not provisioned yet.
     */
    protected function panelServer(): ?array
    {
        $eggId = $this->eggId();
        if ($eggId <= 0) return null;

        try {
            $s = $this->pelican()->getServer($this->service->id, $eggId, false);
            return $s === false ? null : $s;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Aborts 403 if the named visibility toggle is not enabled in product settings.
     */
    protected function requireToggle(string $key): void
    {
        $settings = $this->productSettings();
        if (empty($settings[$key])) abort(403);
    }
}

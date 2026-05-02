<?php

namespace App\Livewire\Components;

use App\Models\CartItem;
use App\Models\Domain;
use App\Models\Product;
use App\Services\Domains\DomainSettings;
use App\Services\Domains\Exceptions\EnomException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Cart-side domain picker.
 *
 * Embed in the cart / product checkout page when the underlying product has
 * `domain_enabled = true`. Lets the customer pick one of the actions the
 * product allows, then persists the selection as `_domain_*` properties on
 * the CartItem so the ProvisionDomainListener picks it up after the order.
 *
 * Usage in a blade file:
 *   @livewire('components.domain-picker', ['cartItem' => $item])
 */
class DomainPicker extends Component
{
    public CartItem $cartItem;

    public string $action = '';
    public string $domain = '';
    public string $authCode = '';
    public string $forwardUrl = '';
    public ?int $parentDomainId = null;
    public int $period = 1;
    public bool $idProtect = false;
    public bool $autoRenew = false;

    public ?bool $availability = null;
    public ?string $availabilityMessage = null;

    public function mount(CartItem $cartItem): void
    {
        $this->cartItem = $cartItem;
        $this->idProtect = (bool) DomainSettings::get('default_id_protect', false);
        $this->autoRenew = (bool) DomainSettings::get('default_auto_renew', true);

        $this->action = (string) $cartItem->properties()->where('key', '_domain_action')->value('value');
        $this->domain = (string) $cartItem->properties()->where('key', '_domain')->value('value');
    }

    #[Computed]
    public function product(): ?Product
    {
        return $this->cartItem->product;
    }

    #[Computed]
    public function options(): array
    {
        return (array) ($this->product?->domain_options ?? []);
    }

    public function checkAvailability(): void
    {
        $this->availability = null;
        $this->availabilityMessage = null;

        $parts = explode('.', strtolower(trim($this->domain, '. ')), 2);
        if (count($parts) !== 2) {
            $this->availabilityMessage = 'Please enter a domain like "example.com".';
            return;
        }

        try {
            $client = DomainSettings::makeEnomClient();
            $result = $client->check($parts[0], $parts[1]);
            $code = (int) data_get($result, 'RRPCode', 0);
            $this->availability = $code === 210;
            $this->availabilityMessage = $this->availability
                ? "{$this->domain} is available!"
                : "{$this->domain} is not available.";
        } catch (EnomException $e) {
            $this->availabilityMessage = 'Lookup failed: ' . $e->getMessage();
        }
    }

    public function save(): void
    {
        $this->validate([
            'action' => 'required|in:register,transfer,custom,subdomain,forward',
            'domain' => 'required|string|max:255',
        ]);

        $values = [
            '_domain_action' => $this->action,
            '_domain' => strtolower(trim($this->domain)),
            '_domain_period' => (string) $this->period,
            '_domain_auth_code' => $this->authCode,
            '_domain_forward_url' => $this->forwardUrl,
            '_domain_parent_id' => $this->parentDomainId,
            '_domain_id_protect' => $this->idProtect ? '1' : '0',
            '_domain_auto_renew' => $this->autoRenew ? '1' : '0',
        ];

        foreach ($values as $key => $value) {
            $this->cartItem->properties()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $this->dispatch('cart.updated');
    }

    public function render()
    {
        return view('livewire.components.domain-picker', [
            'parentDomains' => Domain::query()
                ->where('user_id', auth()->id())
                ->where('type', '!=', Domain::TYPE_SUBDOMAIN)
                ->where('status', Domain::STATUS_ACTIVE)
                ->pluck('domain', 'id'),
        ]);
    }
}

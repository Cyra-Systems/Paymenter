<?php

namespace Paymenter\Extensions\Others\Upsells\Livewire;

use App\Classes\Cart;
use App\Livewire\Component;
use App\Models\Product;

class UpgradeButton extends Component
{
    public $cartItemId;
    public $newProductId;
    public $label = 'Upgrade Now';
    public $primaryColor;
    public $borderRadius = 8;
    public $buttonStyle = 'default';

    public function upgrade()
    {
        $cart = Cart::get();
        $oldItem = $cart->items->firstWhere('id', $this->cartItemId);
        
        if (!$oldItem) {
            return $this->notify('Cart item not found', 'error');
        }

        $newProduct = Product::find($this->newProductId);
        if (!$newProduct) {
            return $this->notify('Product not found', 'error');
        }

        $newPlan = $newProduct->plans->first();
        if (!$newPlan) {
            return $this->notify('No plan available for this product', 'error');
        }

        $oldConfigOptions = collect($oldItem->config_options)->keyBy('option_name');

        $configOptions = $newProduct->configOptions->map(function ($option) use ($newPlan, $oldConfigOptions) {
            $oldOption = $oldConfigOptions->get($option->name);
            
            if (in_array($option->type, ['text', 'number'])) {
                return (object) [
                    'option_id' => $option->id,
                    'option_name' => $option->name,
                    'option_type' => $option->type,
                    'option_env_variable' => $option->env_variable,
                    'value' => $oldOption['value'] ?? null,
                    'value_name' => $oldOption['value_name'] ?? null,
                ];
            }
            
            if ($option->type === 'checkbox') {
                $isChecked = $oldOption && $oldOption['value'] !== null;
                return (object) [
                    'option_id' => $option->id,
                    'option_name' => $option->name,
                    'option_type' => $option->type,
                    'option_env_variable' => $option->env_variable,
                    'value' => $isChecked ? $option->children->first()->id : null,
                    'value_name' => $isChecked ? 'Yes' : 'No',
                ];
            }

            if ($oldOption && isset($oldOption['value_name'])) {
                $matchingChild = $option->children->firstWhere('name', $oldOption['value_name']);
                if ($matchingChild) {
                    return (object) [
                        'option_id' => $option->id,
                        'option_name' => $option->name,
                        'option_type' => $option->type,
                        'option_env_variable' => $option->env_variable,
                        'value' => $matchingChild->id,
                        'value_name' => $matchingChild->name,
                    ];
                }
            }

            $firstChild = $option->children->first();
            return (object) [
                'option_id' => $option->id,
                'option_name' => $option->name,
                'option_type' => $option->type,
                'option_env_variable' => $option->env_variable,
                'value' => $firstChild->id,
                'value_name' => $firstChild->name,
            ];
        });

        $checkoutConfig = $oldItem->checkout_config ?? [];

        Cart::remove($this->cartItemId);
        Cart::add($newProduct, $newPlan, $configOptions, $checkoutConfig);
        
        $this->dispatch('refresh-cart');
        $this->dispatch('cartUpdated');
        $this->notify('Product upgraded successfully', 'success');
    }

    public function render()
    {
        return view('upsells::livewire.upgrade-button');
    }
}


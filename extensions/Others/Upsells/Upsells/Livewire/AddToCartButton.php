<?php

namespace Paymenter\Extensions\Others\Upsells\Livewire;

use App\Classes\Cart;
use App\Livewire\Component;
use App\Models\Product;

class AddToCartButton extends Component
{
    public $targetProductId;
    public $upsellId;
    public $autoAdd = false;
    public $preconfiguredOptions = [];
    public $primaryColor;
    public $borderRadius = 8;
    public $buttonStyle = 'default';

    public function addToCart()
    {
        $product = Product::with(['configOptions.children', 'plans'])->find($this->targetProductId);
        
        if (!$product) {
            return $this->notify('Product not found', 'error');
        }

        $plan = $product->plans->first();
        if (!$plan) {
            return $this->notify('No plan available for this product', 'error');
        }

        if ($this->autoAdd) {
            $preconfigured = collect($this->preconfiguredOptions)->keyBy('option_id');
            
            $configOptions = $product->configOptions->map(function ($option) use ($plan, $preconfigured) {
                $preconfig = $preconfigured->get($option->id);
                
                if (in_array($option->type, ['text', 'number'])) {
                    $value = $preconfig['value'] ?? null;
                    return (object) [
                        'option_id' => $option->id,
                        'option_name' => $option->name,
                        'option_type' => $option->type,
                        'option_env_variable' => $option->env_variable,
                        'value' => $value,
                        'value_name' => $value,
                    ];
                }
                
                if ($option->type === 'checkbox') {
                    $isChecked = isset($preconfig['value']) && $preconfig['value'];
                    return (object) [
                        'option_id' => $option->id,
                        'option_name' => $option->name,
                        'option_type' => $option->type,
                        'option_env_variable' => $option->env_variable,
                        'value' => $isChecked ? $option->children->first()->id : null,
                        'value_name' => $isChecked ? 'Yes' : 'No',
                    ];
                }

                if (isset($preconfig['value']) && $preconfig['value']) {
                    $matchingChild = $option->children->firstWhere('name', $preconfig['value']);
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

            Cart::add($product, $plan, $configOptions, []);
            
            $this->dispatch('refresh-cart');
            $this->dispatch('cartUpdated');
            $this->notify('Added to cart successfully', 'success');
        } else {
            return $this->redirect(route('products.checkout', ['category' => $product->category, 'product' => $product]), true);
        }
    }

    public function render()
    {
        return view('upsells::livewire.add-to-cart-button');
    }
}


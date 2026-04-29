<?php

namespace Paymenter\Extensions\Others\Upsells\Livewire;

use Livewire\Component;
use App\Classes\Cart;
use Paymenter\Extensions\Others\Upsells\Models\Upsell;

class SpendMinimumWidget extends Component
{
    public $upsell;
    public $isUnlocked = false;
    public $totalLeft = 0;
    public $cartTotal = 0;
    
    protected $listeners = ['refresh-cart' => '$refresh', 'cartUpdated' => 'calculateStatus'];
    
    public function mount()
    {
        $this->calculateStatus();
    }
    
    public function calculateStatus()
    {
        $this->cartTotal = Cart::items()->sum(fn ($item) => $item->price->total * $item->quantity);
        $this->isUnlocked = $this->cartTotal >= $this->upsell->minimum_spend;
        $this->totalLeft = max(0, $this->upsell->minimum_spend - $this->cartTotal);
    }
    
    public function claimProduct()
    {
        if (!$this->isUnlocked) {
            return;
        }
        
        if (auth()->check()) {
            $upsell = Upsell::find($this->upsell->id);
            
            if (!$upsell->allow_multiple_claims) {
                
                $hasService = auth()->user()->services()
                    ->where('product_id', $upsell->target_product_id)
                    ->exists();
                
                if ($hasService) {
                    session()->flash('error', 'You have already claimed this offer. This offer can only be claimed once.');
                    return;
                }
            } else {
                
                if ($upsell->max_claims !== null && $upsell->max_claims > 0) {
                    $claimCount = auth()->user()->services()
                        ->where('product_id', $upsell->target_product_id)
                        ->count();
                    
                    if ($claimCount >= $upsell->max_claims) {
                        session()->flash('error', "You have reached the maximum claim limit of {$upsell->max_claims} for this offer.");
                        return;
                    }
                }
            }
        }
        
        $alreadyInCart = Cart::items()->contains(function ($item) {
            return $item->product_id === $this->upsell->target_product_id;
        });
        
        if ($alreadyInCart) {
            session()->flash('error', 'This product is already in your cart');
            return;
        }
        
        $product = \App\Models\Product::find($this->upsell->target_product_id);
        
        if (!$product) {
            session()->flash('error', 'Product not found');
            return;
        }
        
        if ($product->configOptions()->count() > 0) {
            return redirect()->route('products.checkout', [
                $product->category, 
                $product, 
                'upsell_id' => $this->upsell->id
            ]);
        }
        
        $plan = $product->plans->first();
        if (!$plan) {
            session()->flash('error', 'No plans available for this product');
            return;
        }
        
        $cartItemId = Cart::add($product, $plan, [], [], 1);
        
        $cartItem = \App\Models\CartItem::find($cartItemId);
        if ($cartItem) {
            $totalPrice = $cartItem->price->price + $cartItem->price->setup_fee;
            $cartItem->promotional_discount = $totalPrice;
            $cartItem->promotional_type = 'spend_minimum_' . $this->upsell->id;
            $cartItem->save();
        }
        
        $this->dispatch('refresh-cart');
        $this->dispatch('cartUpdated');
        
        session()->flash('success', 'Free product added to cart!');
    }
    
    public function render()
    {
        return view('upsells::livewire.spend-minimum-widget');
    }
}


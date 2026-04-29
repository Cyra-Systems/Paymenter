<?php

namespace Paymenter\Extensions\Others\Upsells;

use App\Classes\Extension\Extension;
use App\Helpers\ExtensionHelper;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use Paymenter\Extensions\Others\Upsells\Models\Upsell;
use Paymenter\Extensions\Others\Upsells\Models\UpsellSettings;
use Paymenter\Extensions\Others\Upsells\Livewire\UpgradeButton;
use Paymenter\Extensions\Others\Upsells\Livewire\AddToCartButton;
use Paymenter\Extensions\Others\Upsells\Livewire\SpendMinimumWidget;
use Paymenter\Extensions\Others\Upsells\Admin\Pages\UpsellDesignSettings;
use App\Classes\Cart;
use Filament\Facades\Filament;

class Upsells extends Extension
{
    public function getConfig($values = [])
    {
        return [];
    }

    public function installed()
    {
        $this->runExtensionMigrations();
    }

    public function enabled()
    {
        $this->runExtensionMigrations();
    }

    public function uninstalled()
    {
        $this->rollbackExtensionMigrations();
    }

    public function upgraded($oldVersion = null)
    {
        $this->runExtensionMigrations();
    }

    private function runExtensionMigrations(): void
    {
        if (method_exists(ExtensionHelper::class, 'runMigrations')) {
            ExtensionHelper::runMigrations(__DIR__ . '/database/migrations');
            return;
        }
        Artisan::call('migrate', [
            '--path' => 'extensions/Others/Upsells/database/migrations',
            '--force' => true,
        ]);
    }

    private function rollbackExtensionMigrations(): void
    {
        if (method_exists(ExtensionHelper::class, 'rollbackMigrations')) {
            ExtensionHelper::rollbackMigrations(__DIR__ . '/database/migrations');
            return;
        }
        Artisan::call('migrate:rollback', [
            '--path' => 'extensions/Others/Upsells/database/migrations',
            '--force' => true,
        ]);
    }

    public function boot()
    {
        View::addNamespace('upsells', __DIR__ . '/resources/views');

        Livewire::component('upsells-upgrade-button', UpgradeButton::class);
        Livewire::component('upsells-add-to-cart-button', AddToCartButton::class);
        Livewire::component('upsells-spend-minimum-widget', SpendMinimumWidget::class);

        Filament::registerPages([
            UpsellDesignSettings::class,
        ]);
        
        $this->registerPromotionalDiscountMacro();

        View::composer('cart', function ($view) {
            $view->with('upsellSettings', UpsellSettings::get());
            $view->with('spendMinimumUpsells', $this->getSpendMinimumUpsells());
            $view->with('calculatePromotionalTotal', function () {
                $cart = Cart::get();
                if (!$cart || !$cart->items) {
                    return 0;
                }
                
                $total = 0;
                foreach ($cart->items as $item) {
                    $itemTotal = $item->price->total * $item->quantity;
                    
                    if ($item->promotional_discount && $item->promotional_discount > 0) {
                        $itemTotal = max(0, $itemTotal - ($item->promotional_discount * $item->quantity));
                    }
                    
                    $total += $itemTotal;
                }
                
                return $total;
            });
            $view->with('getUpsellsForProduct', function ($productId, $cartItemId = null) {
                try {
                    $cart = Cart::get();
                    if (!$cart) {
                        \Log::debug("Upsells: No cart found for product {$productId}");
                        return collect();
                    }

                    $upsells = Upsell::with(['targetProduct.category', 'targetProduct.plans', 'triggerProduct'])
                        ->where('enabled', true)
                        ->where('trigger_product_id', $productId)
                        ->orderBy('priority', 'desc')
                        ->get();

                    \Log::debug("Upsells: Found {$upsells->count()} upsells for product {$productId} (types: " . $upsells->pluck('type')->implode(', ') . ")");

                    if ($upsells->isEmpty()) {
                        return collect();
                    }

                    $filtered = $upsells->filter(function ($upsell) use ($cart, $productId) {
                        \Log::debug("Upsells: Filtering upsell ID {$upsell->id}, type: {$upsell->type}");
                        
                        
                        if (!$upsell->targetProduct) {
                            \Log::debug("Upsells: Upsell {$upsell->id} filtered - no targetProduct");
                            return false;
                        }

                        if ($upsell->type === 'upgrade' && !$upsell->triggerProduct) {
                            \Log::debug("Upsells: Upsell {$upsell->id} filtered - upgrade without triggerProduct");
                            return false;
                        }

                        if (!$upsell->targetProduct->plans || $upsell->targetProduct->plans->isEmpty()) {
                            \Log::debug("Upsells: Upsell {$upsell->id} filtered - no plans for target product");
                            return false;
                        }

                        if ($upsell->type === 'upgrade') {
                            $userOwnsTarget = false;
                            if (auth()->check()) {
                                $userOwnsTarget = auth()->user()->services()
                                    ->where('product_id', $upsell->target_product_id)
                                    ->where('status', 'active')
                                    ->exists();
                            }

                            if ($userOwnsTarget) {
                                \Log::debug("Upsells: Upsell {$upsell->id} filtered - user owns target product (upgrade)");
                                return false;
                            }
                        }

                        $alreadyInCart = $cart->items->contains(function ($item) use ($upsell) {
                            return $item->product_id === $upsell->target_product_id;
                        });

                        if ($alreadyInCart) {
                            \Log::debug("Upsells: Upsell {$upsell->id} filtered - already in cart");
                            return false;
                        }

                        if ($upsell->type === 'cross_sell') {
                            $targetHasBeenUpgraded = Upsell::where('enabled', true)
                                ->where('type', 'upgrade')
                                ->where('trigger_product_id', $upsell->target_product_id)
                                ->get()
                                ->contains(function ($upgradeUpsell) use ($cart) {
                                    return $cart->items->contains(function ($item) use ($upgradeUpsell) {
                                        return $item->product_id === $upgradeUpsell->target_product_id;
                                    });
                                });

                            if ($targetHasBeenUpgraded) {
                                \Log::debug("Upsells: Upsell {$upsell->id} filtered - target has been upgraded");
                                return false;
                            }
                        }

                        \Log::debug("Upsells: Upsell {$upsell->id} passed all filters");
                        return true;
                    });
                    
                    \Log::debug("Upsells: After filtering, {$filtered->count()} upsells remain for product {$productId}");
                    return $filtered;
                } catch (\Exception $e) {
                    \Log::error('Upsells error: ' . $e->getMessage());
                    \Log::error('Upsells error trace: ' . $e->getTraceAsString());
                    return collect();
                }
            });
        });
    }
    
    private function getSpendMinimumUpsells()
    {
        try {
            $cart = Cart::get();
            if (!$cart) {
                \Log::debug('Spend minimum: No cart found');
                return collect();
            }
            
            $upsells = Upsell::with(['targetProduct'])
                ->where('enabled', true)
                ->where('type', 'spend_minimum')
                ->orderBy('minimum_spend', 'asc')
                ->get();
            
            \Log::debug('Spend minimum upsells found: ' . $upsells->count());
            
            if ($upsells->isEmpty()) {
                return collect();
            }
            
            return $upsells->filter(function ($upsell) use ($cart) {
                if (!$upsell->targetProduct) {
                    return false;
                }
                
                $alreadyInCart = $cart->items->contains(function ($item) use ($upsell) {
                    return isset($item->product) && $item->product->id === $upsell->target_product_id;
                });
                
                if ($alreadyInCart) {
                    return false;
                }
                
                if (auth()->check()) {
                    if (!$upsell->allow_multiple_claims) {
                        
                        $userOwnsTarget = auth()->user()->services()
                            ->where('product_id', $upsell->target_product_id)
                            ->exists();
                        
                        if ($userOwnsTarget) {
                            return false;
                        }
                    } else {
                        
                        if ($upsell->max_claims !== null && $upsell->max_claims > 0) {
                            $claimCount = auth()->user()->services()
                                ->where('product_id', $upsell->target_product_id)
                                ->count();
                            
                            if ($claimCount >= $upsell->max_claims) {
                                return false;
                            }
                        }
                    }
                }
                
                return true;
            });
        } catch (\Exception $e) {
            \Log::error('Spend minimum upsells error: ' . $e->getMessage());
            return collect();
        }
    }
    
    private function registerPromotionalDiscountMacro()
    {
        Event::listen('eloquent.retrieved: App\Models\CartItem', function ($cartItem) {
            if ($cartItem->promotional_discount && $cartItem->promotional_discount > 0) {
                $originalPrice = $cartItem->getRawOriginal('price');
                
                \App\Models\CartItem::resolveRelationUsing('promotional_price', function ($cartItem) {
                    return $cartItem->price;
                });
            }
        });
        
        \App\Models\CartItem::creating(function ($cartItem) {
            if (request()->has('upsell_id')) {
                $upsellId = request()->get('upsell_id');
                $upsell = Upsell::find($upsellId);
                
                if ($upsell && $upsell->type === 'spend_minimum') {
                    $cartItem->promotional_type = 'spend_minimum_' . $upsellId;
                }
            }
        });
    }
}


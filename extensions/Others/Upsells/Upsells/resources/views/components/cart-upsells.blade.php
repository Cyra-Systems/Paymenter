@props(['item', 'settings' => null, 'getUpsellsForProduct' => null])

@php
    use Paymenter\Extensions\Others\Upsells\Models\Upsell;
    use App\Classes\Cart;

    if ($getUpsellsForProduct && is_callable($getUpsellsForProduct)) {
        $upsells = $getUpsellsForProduct($item->product->id, $item->id);
    } else {
        $cart = Cart::get();
        if (!$cart) {
            $upsells = collect();
        } else {
            $upsells = Upsell::with(['targetProduct.category', 'targetProduct.plans', 'triggerProduct'])
                ->where('enabled', true)
                ->where('trigger_product_id', $item->product->id)
                ->orderBy('priority', 'desc')
                ->get()
                ->filter(function ($upsell) use ($cart, $item) {
                    if (!$upsell->targetProduct) {
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
                            return false;
                        }
                    }

                    $alreadyInCart = $cart->items->contains(function ($cartItem) use ($upsell) {
                        return $cartItem->product_id === $upsell->target_product_id;
                    });

                    if ($alreadyInCart) {
                        return false;
                    }

                    if ($upsell->type === 'upgrade') {
                        $hasUpgradedHigher = $cart->items->contains(function ($cartItem) use ($upsell) {
                            if ($cartItem->product_id === $upsell->trigger_product_id) {
                                return false;
                            }

                            $upgradeUpsells = Upsell::where('trigger_product_id', $upsell->trigger_product_id)
                                ->where('type', 'upgrade')
                                ->pluck('target_product_id');

                            return $upgradeUpsells->contains($cartItem->product_id);
                        });

                        if ($hasUpgradedHigher) {
                            return false;
                        }
                    }

                    return true;
                });
        }
    }
@endphp

@if ($upsells && $upsells->isNotEmpty())
    <div class="ml-10 mt-5">
        @foreach ($upsells as $upsell)
            @include('upsells::components.upsell-item', [
                'upsell' => $upsell,
                'item' => $item,
                'settings' => $settings ?? null,
            ])
        @endforeach
    </div>
@endif


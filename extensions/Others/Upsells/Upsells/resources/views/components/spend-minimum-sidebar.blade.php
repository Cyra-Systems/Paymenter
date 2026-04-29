@props(['settings' => null, 'spendMinimumUpsells' => null])

@php
    use App\Classes\Cart;
    use Paymenter\Extensions\Others\Upsells\Models\Upsell;

    $spendMinimumUpsells = Upsell::with(['targetProduct'])
        ->where('type', 'spend_minimum')
        ->whereIn('template', ['sidebar', 'sidebar-progress'])
        ->where('enabled', true)
        ->orderBy('priority', 'desc')
        ->get()
        ->filter(function ($upsell) {
            if (!$upsell->targetProduct) {
                return false;
            }

                $cart = Cart::get();
                if (!$cart || !$cart->items) {
                    return false;
                }

                $alreadyInCart = $cart->items->contains(function ($item) use ($upsell) {
                    return $item->product_id === $upsell->target_product_id;
                });

                if ($alreadyInCart) {
                    return false;
                }

                if ($upsell->allow_multiple_claims === false || ($upsell->max_claims && $upsell->max_claims > 0)) {
                    $claimCount = $cart->items->filter(function ($item) use ($upsell) {
                        return $item->product_id === $upsell->target_product_id 
                            && isset($item->promotional_type) 
                            && $item->promotional_type === 'spend_minimum';
                    })->sum('quantity');

                    if ($upsell->allow_multiple_claims === false && $claimCount > 0) {
                        return false;
                    }

                    if ($upsell->max_claims && $upsell->max_claims > 0 && $claimCount >= $upsell->max_claims) {
                        return false;
                    }
                }

                return true;
            });
@endphp

@if ($spendMinimumUpsells && $spendMinimumUpsells->isNotEmpty() && Cart::items()->count() > 0)
    @foreach ($spendMinimumUpsells as $spendMinimumUpsell)
        @livewire(
            'upsells-spend-minimum-widget',
            [
                'upsell' => $spendMinimumUpsell,
                'upsellSettings' => $settings ?? null,
            ],
            key('spend-min-sidebar-' . $spendMinimumUpsell->id)
        )
    @endforeach
@endif


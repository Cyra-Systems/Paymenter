# Theme Integration Guide

This guide shows you how to add upsell widgets to your Paymenter theme. Please note for Luna, Cosmos & Opal customers, this is already included in the theme for you.

## Prerequisites

1. The Upsells extension must be installed and enabled
2. Run the database migrations before integrating into your theme:

```bash
php artisan migrate --path=extensions/Others/Upsells/database/migrations
```

This will create the database tables for the extension

## Three Upsell Hooks

There are three places where upsells can appear in your cart:

1. **Top of cart** for spend minimum upsells (non-sidebar variants)
2. **Below each product** for product upgrade and "often ordered with" upsells
3. **Sidebar** for spend minimum sidebar widgets (optional, only if your theme has a sidebar)

## Integration

### 1. Top of Cart Hook

Place this near the top of your cart items section, before the product loop.

Location: After the empty cart check, before `@foreach (Cart::items() as $item)`

```blade
@includeWhen(View::exists('upsells::components.spend-minimum-top'), 'upsells::components.spend-minimum-top', [
    'settings' => $upsellSettings ?? null,
    'spendMinimumUpsells' => $spendMinimumUpsells ?? null
])
```

### 2. Product Upsells Hook

Place this inside your cart items loop, after each product card closes.

Location: Inside `@foreach (Cart::items() as $item)`, after the product display code

```blade
@includeWhen(View::exists('upsells::components.cart-upsells'), 'upsells::components.cart-upsells', [
    'item' => $item,
    'settings' => $upsellSettings ?? null,
    'getUpsellsForProduct' => $getUpsellsForProduct ?? null
])
```

### 3. Sidebar Hook (Optional)

Only add this if your theme has a sidebar in the cart view.

Location: Inside your sidebar column, before the order summary card

```blade
@includeWhen(View::exists('upsells::components.spend-minimum-sidebar'), 'upsells::components.spend-minimum-sidebar', [
    'settings' => $upsellSettings ?? null,
    'spendMinimumUpsells' => $spendMinimumUpsells ?? null
])
```

## Live Updates (Optional)

For real-time updates when users interact with upsells, add this to your main cart container:

```blade
<div x-data @refresh-cart.window="setTimeout(() => $wire.$refresh(), 100)">
    <!-- Your existing cart content -->
</div>
```

## Example Structure

Here's how your cart view should look:

```blade
<div x-data @refresh-cart.window="setTimeout(() => $wire.$refresh(), 100)">
    <div class="cart-items">
        @if (Cart::items()->count() === 0)
            <h1>Your cart is empty</h1>
        @endif

        <!-- 1. Top of cart hook -->
        @includeWhen(View::exists('upsells::components.spend-minimum-top'), 'upsells::components.spend-minimum-top', [
            'settings' => $upsellSettings ?? null,
            'spendMinimumUpsells' => $spendMinimumUpsells ?? null
        ])

        @foreach (Cart::items() as $item)
            <div class="cart-item">
                <!-- Your product display code -->
            </div>

            <!-- 2. Product upsells hook -->
            @includeWhen(View::exists('upsells::components.cart-upsells'), 'upsells::components.cart-upsells', [
                'item' => $item,
                'settings' => $upsellSettings ?? null,
                'getUpsellsForProduct' => $getUpsellsForProduct ?? null
            ])
        @endforeach
    </div>

    <div class="sidebar">
        <!-- 3. Sidebar hook (optional) -->
        @includeWhen(View::exists('upsells::components.spend-minimum-sidebar'), 'upsells::components.spend-minimum-sidebar', [
            'settings' => $upsellSettings ?? null,
            'spendMinimumUpsells' => $spendMinimumUpsells ?? null
        ])

        <!-- Your order summary -->
    </div>
</div>
```

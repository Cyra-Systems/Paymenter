<div class="upsells-container my-6">
    @php
        $upgradeUpsells = $upsells->where('type', 'upgrade');
        $crossSellUpsells = $upsells->where('type', 'cross_sell');
    @endphp

    @if($upgradeUpsells->isNotEmpty())
        <div class="upgrade-upsells mb-6">
            <h3 class="text-xl font-semibold mb-4">Consider Upgrading</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($upgradeUpsells as $upsell)
                    @php
                        $targetProduct = $upsell->targetProduct;
                        $triggerProduct = $upsell->triggerProduct;
                        if (!$targetProduct || !$triggerProduct) continue;
                        
                        $targetPlan = $targetProduct->plans->first();
                        $triggerPlan = $triggerProduct->plans->first();
                        
                        if (!$targetPlan || !$triggerPlan) continue;
                        
                        $priceDiff = $targetPlan->price()->price - $triggerPlan->price()->price;
                        $currency = $targetPlan->price()->currency;
                    @endphp
                    
                    <div class="upsell-card border rounded-lg p-4 bg-background-secondary hover:border-primary transition-colors relative">
                        @if($upsell->label)
                            <span class="absolute top-2 right-2 text-xs font-semibold px-2 py-1 rounded bg-primary/10 text-primary">
                                {{ $upsell->label }}
                            </span>
                        @endif
                        
                        <div class="mb-3">
                            <h4 class="text-lg font-semibold">
                                {{ $upsell->title ?: 'Upgrade to ' . $targetProduct->name }}
                            </h4>
                            @if($upsell->show_price_difference && $priceDiff > 0)
                                <p class="text-sm text-muted mt-1">
                                    Only {{ number_format($priceDiff, 2) }} {{ $currency }} more
                                </p>
                            @endif
                        </div>
                        
                        @if($upsell->description)
                            <p class="text-sm text-muted mb-4">{{ $upsell->description }}</p>
                        @endif
                        
                        <a href="{{ route('products.checkout', ['category' => $targetProduct->category, 'product' => $targetProduct]) }}" 
                           class="inline-block px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 transition-colors">
                            Upgrade Now
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($crossSellUpsells->isNotEmpty())
        <div class="cross-sell-upsells">
            <h3 class="text-xl font-semibold mb-4">You Might Also Like</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($crossSellUpsells as $upsell)
                    @php
                        $targetProduct = $upsell->targetProduct;
                        if (!$targetProduct) continue;
                        
                        $targetPlan = $targetProduct->plans->first();
                        if (!$targetPlan) continue;
                    @endphp
                    
                    <div class="upsell-card border rounded-lg p-4 bg-background-secondary hover:border-primary transition-colors relative">
                        @if($upsell->label)
                            <span class="absolute top-2 right-2 text-xs font-semibold px-2 py-1 rounded bg-secondary/10 text-secondary">
                                {{ $upsell->label }}
                            </span>
                        @endif
                        
                        <div class="mb-3">
                            <h4 class="text-lg font-semibold">
                                {{ $upsell->title ?: $targetProduct->name }}
                            </h4>
                            <p class="text-sm text-muted mt-1">
                                {{ $targetPlan->price()->formatted->price }}
                            </p>
                        </div>
                        
                        @if($upsell->description)
                            <p class="text-sm text-muted mb-4">{{ $upsell->description }}</p>
                        @endif
                        
                        <a href="{{ route('products.checkout', ['category' => $targetProduct->category, 'product' => $targetProduct]) }}" 
                           class="inline-block px-4 py-2 bg-secondary text-white rounded hover:bg-secondary/90 transition-colors">
                            Add to Cart
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>


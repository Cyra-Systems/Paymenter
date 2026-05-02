<div class="container mt-14 space-y-4">
    <x-navigation.breadcrumb />

    <div class="flex flex-row items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $domain->fqdn }}</h1>
            <p class="text-sm opacity-70">{{ $domain->registrar }} — {{ $domain->status }}</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="refreshInfo" class="px-3 py-2 rounded-lg border border-neutral text-sm">Sync</button>
            <a href="{{ route('domains.dns', $domain) }}" wire:navigate
                class="px-3 py-2 rounded-lg border border-neutral text-sm">DNS</a>
            <a href="{{ route('domains.nameservers', $domain) }}" wire:navigate
                class="px-3 py-2 rounded-lg border border-neutral text-sm">Nameservers</a>
            <a href="{{ route('domains.bind', $domain) }}" wire:navigate
                class="px-3 py-2 rounded-lg bg-primary text-white text-sm">Bind to a service</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-background-secondary border border-neutral p-4 rounded-lg space-y-2">
            <h2 class="text-sm font-semibold uppercase opacity-70">Registrar info</h2>
            <p class="text-sm">Expires: {{ $domain->expires_at?->format('M d, Y') ?: '—' }}</p>
            <p class="text-sm">Auto-renew: {{ $domain->auto_renew ? 'on' : 'off' }}</p>
            <p class="text-sm">Registrar lock: {{ $domain->locked ? 'locked' : 'unlocked' }}</p>
            <p class="text-sm">WHOIS privacy: {{ $domain->id_protect ? 'on' : 'off' }}</p>
        </div>

        <div class="bg-background-secondary border border-neutral p-4 rounded-lg space-y-2">
            <h2 class="text-sm font-semibold uppercase opacity-70">Service binding</h2>
            @if ($binding)
                <p class="text-sm">Bound to: service #{{ $binding->service_id }}
                    {{ $binding->service?->product?->name }}</p>
                <p class="text-sm">Hostname: {{ $binding->hostname }}</p>
                <p class="text-sm">Type: {{ $binding->type }}</p>
                <p class="text-sm">Status: {{ $binding->status }}</p>
            @else
                <p class="text-sm opacity-70">This domain is not bound to a service yet.</p>
            @endif
        </div>
    </div>

    @if (! empty($info['nameservers']))
        <div class="bg-background-secondary border border-neutral p-4 rounded-lg">
            <h2 class="text-sm font-semibold uppercase opacity-70 mb-2">Live nameservers</h2>
            <ul class="text-sm space-y-1">
                @foreach ($info['nameservers'] as $ns)
                    <li>{{ $ns }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<div class="container mt-14 space-y-4">
    <x-navigation.breadcrumb />

    <h1 class="text-2xl font-semibold">Bind {{ $domain->fqdn }}</h1>
    <p class="text-sm opacity-70">Move this domain between any of your active services. The reverse proxy will be
        reconfigured automatically and the new server's hostname will be updated.</p>

    <div class="bg-background-secondary border border-neutral p-4 rounded-lg space-y-3">
        <div>
            <label class="text-xs opacity-70">Target service</label>
            <select class="w-full bg-transparent border border-neutral rounded px-3 py-2"
                wire:model="targetServiceId">
                <option value="">-- pick a service --</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}">#{{ $service->id }} — {{ $service->product->name ?? 'Service' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-xs opacity-70">Binding type</label>
            <select class="w-full bg-transparent border border-neutral rounded px-3 py-2" wire:model="bindingType">
                <option value="primary">Primary (proxy this domain to the service)</option>
                <option value="forward">Forward (HTTP redirect to another URL)</option>
                <option value="subdomain">Subdomain (under our system base)</option>
                <option value="custom">Custom (no proxy)</option>
            </select>
        </div>

        <div>
            <label class="text-xs opacity-70">Hostname</label>
            <input type="text" class="w-full bg-transparent border border-neutral rounded px-3 py-2"
                wire:model="hostname" />
            <p class="text-xs opacity-50 mt-1">For subdomains use the form
                <code>&lt;prefix&gt;.{{ config('settings.domains.subdomain_base') }}</code>.
            </p>
        </div>

        <button wire:click="bind" class="px-3 py-2 rounded-lg bg-primary text-white text-sm">Confirm bind</button>
    </div>
</div>

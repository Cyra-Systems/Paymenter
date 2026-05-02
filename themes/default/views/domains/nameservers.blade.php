<div class="container mt-14 space-y-4">
    <x-navigation.breadcrumb />

    <h1 class="text-2xl font-semibold">Nameservers — {{ $domain->fqdn }}</h1>

    <div class="bg-background-secondary border border-neutral p-4 rounded-lg space-y-3">
        @foreach ($nameservers as $i => $ns)
            <div>
                <label class="text-xs opacity-70">NS{{ $i + 1 }}</label>
                <input type="text" class="w-full bg-transparent border border-neutral rounded px-3 py-2"
                    wire:model="nameservers.{{ $i }}" placeholder="ns1.example.com" />
            </div>
        @endforeach
        <button wire:click="save" class="px-3 py-2 rounded-lg bg-primary text-white text-sm">Save</button>
    </div>
</div>

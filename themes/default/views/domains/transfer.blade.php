<div class="container mt-14 space-y-4">
    <x-navigation.breadcrumb />

    <h1 class="text-2xl font-semibold">Transfer a domain</h1>
    <p class="text-sm opacity-70">Bring an existing domain registration to us. Make sure the registrar lock is off
        and you have an EPP/auth code from your current registrar.</p>

    <div class="bg-background-secondary border border-neutral p-4 rounded-lg space-y-3">
        <div>
            <label class="text-xs opacity-70">Domain (FQDN)</label>
            <input type="text" class="w-full bg-transparent border border-neutral rounded px-3 py-2"
                wire:model="fqdn" placeholder="example.com" />
        </div>
        <div>
            <label class="text-xs opacity-70">EPP / Auth code</label>
            <input type="text" class="w-full bg-transparent border border-neutral rounded px-3 py-2"
                wire:model="authCode" />
        </div>
        <button wire:click="transfer" class="px-3 py-2 rounded-lg bg-primary text-white text-sm">Start transfer</button>
    </div>
</div>

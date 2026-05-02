<div class="container mt-14 space-y-4">
    <x-navigation.breadcrumb />

    <h1 class="text-2xl font-semibold">Register a domain</h1>

    <div class="bg-background-secondary border border-neutral p-4 rounded-lg space-y-3">
        <div class="flex flex-col md:flex-row gap-2">
            <div class="flex-1">
                <label class="text-xs opacity-70">SLD</label>
                <input type="text" class="w-full bg-transparent border border-neutral rounded px-3 py-2"
                    wire:model="sld" placeholder="example" />
            </div>
            <div class="w-32">
                <label class="text-xs opacity-70">TLD</label>
                <input type="text" class="w-full bg-transparent border border-neutral rounded px-3 py-2"
                    wire:model="tld" placeholder="com" />
            </div>
            <div class="w-24">
                <label class="text-xs opacity-70">Years</label>
                <input type="number" class="w-full bg-transparent border border-neutral rounded px-3 py-2"
                    wire:model="years" min="1" max="10" />
            </div>
        </div>

        <div class="flex items-center gap-4 text-sm">
            <label><input type="checkbox" wire:model="idProtect" /> WHOIS privacy</label>
            <label><input type="checkbox" wire:model="autoRenew" /> Auto-renew</label>
        </div>

        <div class="flex gap-2">
            <button wire:click="check" class="px-3 py-2 rounded-lg border border-neutral text-sm">Check
                availability</button>
            <button wire:click="register" class="px-3 py-2 rounded-lg bg-primary text-white text-sm">Register</button>
        </div>

        @if ($availability)
            <p class="text-sm">{{ $availability }}</p>
        @endif
    </div>

    @if ($tlds->count() > 0)
        <div class="bg-background-secondary border border-neutral p-4 rounded-lg">
            <h2 class="text-sm font-semibold uppercase opacity-70 mb-2">Available TLDs</h2>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left">
                        <th>TLD</th>
                        <th>Register</th>
                        <th>Renewal</th>
                        <th>Transfer</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tlds as $tld)
                        <tr class="border-t border-neutral/40">
                            <td>.{{ $tld->tld }}</td>
                            <td>{{ $tld->currency_code }} {{ number_format($tld->priceWithMargin('register_price'), 2) }}
                            </td>
                            <td>{{ $tld->currency_code }} {{ number_format($tld->priceWithMargin('renewal_price'), 2) }}
                            </td>
                            <td>{{ $tld->currency_code }} {{ number_format($tld->priceWithMargin('transfer_price'), 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

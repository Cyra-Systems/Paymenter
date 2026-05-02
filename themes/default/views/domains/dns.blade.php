<div class="container mt-14 space-y-4">
    <x-navigation.breadcrumb />

    <div class="flex flex-row items-center justify-between">
        <h1 class="text-2xl font-semibold">DNS — {{ $domain->fqdn }}</h1>
        <div class="flex gap-2">
            <button wire:click="addRecord" class="px-3 py-2 rounded-lg border border-neutral text-sm">Add record</button>
            <button wire:click="save" class="px-3 py-2 rounded-lg bg-primary text-white text-sm">Save</button>
        </div>
    </div>

    @if (! empty($nameservers))
        <p class="text-xs opacity-70">Nameservers: {{ implode(', ', $nameservers) }}</p>
    @endif

    <div class="bg-background-secondary border border-neutral p-4 rounded-lg">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left">
                    <th class="py-2">Hostname</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Priority</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $i => $record)
                    <tr class="border-t border-neutral/40">
                        <td class="py-2 pr-2"><input type="text" class="w-full bg-transparent border border-neutral rounded px-2 py-1"
                                wire:model.live.debounce.300ms="records.{{ $i }}.hostname" /></td>
                        <td class="pr-2"><input type="text" class="w-24 bg-transparent border border-neutral rounded px-2 py-1"
                                wire:model.live.debounce.300ms="records.{{ $i }}.type" /></td>
                        <td class="pr-2"><input type="text" class="w-full bg-transparent border border-neutral rounded px-2 py-1"
                                wire:model.live.debounce.300ms="records.{{ $i }}.address" /></td>
                        <td class="pr-2"><input type="number" class="w-20 bg-transparent border border-neutral rounded px-2 py-1"
                                wire:model.live.debounce.300ms="records.{{ $i }}.priority" /></td>
                        <td><button wire:click="removeRecord({{ $i }})" class="text-danger text-xs">Remove</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if (empty($records))
            <p class="text-sm opacity-70">No DNS records yet — click "Add record" above.</p>
        @endif
    </div>
</div>

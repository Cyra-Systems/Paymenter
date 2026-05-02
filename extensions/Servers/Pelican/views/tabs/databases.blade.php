<div>
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                <x-ri-database-2-line class="size-5" />
            </div>
            <h2 class="text-xl font-semibold">Databases</h2>
        </div>
        <x-button.primary wire:click="$toggle('creating')">
            <x-ri-add-line class="size-4" /> New Database
        </x-button.primary>
    </div>

    @if($creating)
    <form wire:submit.prevent="create" class="bg-background-secondary border border-neutral p-4 rounded-lg mb-4 space-y-3">
        <div>
            <label class="text-sm text-base/70">Name</label>
            <input type="text" wire:model="newName" required
                class="w-full bg-background border border-neutral rounded-md px-3 py-2 mt-1" />
        </div>
        <div>
            <label class="text-sm text-base/70">Connections from (host)</label>
            <input type="text" wire:model="newRemote"
                class="w-full bg-background border border-neutral rounded-md px-3 py-2 mt-1 font-mono text-sm" />
        </div>
        <div class="flex gap-2">
            <x-button.primary type="submit">Create</x-button.primary>
            <x-button.secondary type="button" wire:click="$set('creating', false)">Cancel</x-button.secondary>
        </div>
    </form>
    @endif

    <div class="bg-background-secondary border border-neutral rounded-lg overflow-hidden">
        @forelse($databases as $db)
        <div class="p-4 border-b border-neutral last:border-0">
            <div class="flex items-center justify-between mb-2">
                <span class="font-semibold">{{ $db['name'] ?? 'unnamed' }}</span>
                <div class="flex gap-2">
                    <x-button.secondary wire:click="rotate('{{ $db['id'] }}')"
                        wire:confirm="Rotate password?">
                        <x-ri-key-line class="size-4" /> Rotate
                    </x-button.secondary>
                    <x-button.danger wire:click="delete('{{ $db['id'] }}')"
                        wire:confirm="Delete database {{ $db['name'] }}? This cannot be undone.">
                        <x-ri-delete-bin-line class="size-4" />
                    </x-button.danger>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div><span class="text-base/50">Host:</span> <span class="font-mono">{{ $db['host']['address'] ?? ($db['host'] ?? '?') }}:{{ $db['host']['port'] ?? '' }}</span></div>
                <div><span class="text-base/50">User:</span> <span class="font-mono">{{ $db['username'] ?? '?' }}</span></div>
                <div><span class="text-base/50">Password:</span> <span class="font-mono">{{ $db['relationships']['password']['attributes']['password'] ?? '••••' }}</span></div>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-base/50 text-sm">No databases yet.</div>
        @endforelse
    </div>
</div>

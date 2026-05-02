<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
            <x-ri-team-line class="size-5" />
        </div>
        <h2 class="text-xl font-semibold">Subusers</h2>
    </div>

    <form wire:submit.prevent="add" class="bg-background-secondary border border-neutral p-4 rounded-lg mb-4 space-y-3">
        <div>
            <label class="text-sm text-base/70">Email</label>
            <input type="email" wire:model="newEmail" required
                class="w-full bg-background border border-neutral rounded-md px-3 py-2 mt-1" />
        </div>

        <div>
            <label class="text-sm text-base/70">Permissions</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2 max-h-64 overflow-y-auto p-2 border border-neutral rounded-md">
                @foreach($allPermissions as $key => $label)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="newPermissions" value="{{ $key }}" />
                        <span>{{ $label }}</span>
                        <code class="text-xs text-base/40 ml-auto">{{ $key }}</code>
                    </label>
                @endforeach
            </div>
        </div>

        <x-button.primary type="submit">Add Subuser</x-button.primary>
    </form>

    <div class="bg-background-secondary border border-neutral rounded-lg overflow-hidden">
        @forelse($subusers as $u)
        <div class="p-4 border-b border-neutral last:border-0 flex items-center justify-between">
            <div>
                <div class="font-semibold">{{ $u['username'] ?? $u['email'] }}</div>
                <div class="text-xs text-base/50">{{ $u['email'] ?? '' }}</div>
                <div class="text-xs text-base/50 mt-1">{{ count($u['permissions'] ?? []) }} permission(s)</div>
            </div>
            <x-button.danger wire:click="remove('{{ $u['uuid'] }}')" wire:confirm="Remove this subuser?">
                <x-ri-delete-bin-line class="size-4" />
            </x-button.danger>
        </div>
        @empty
        <div class="p-8 text-center text-base/50 text-sm">No subusers.</div>
        @endforelse
    </div>
</div>

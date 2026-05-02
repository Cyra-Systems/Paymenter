<div>
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                <x-ri-archive-line class="size-5" />
            </div>
            <h2 class="text-xl font-semibold">Backups</h2>
        </div>
        <x-button.primary wire:click="create">
            <x-loading target="create" />
            <x-ri-add-line class="size-4" />
            <span wire:loading.remove wire:target="create">New Backup</span>
        </x-button.primary>
    </div>

    <div class="bg-background-secondary border border-neutral rounded-lg overflow-hidden">
        @forelse($backups as $b)
        <div class="p-4 border-b border-neutral last:border-0 flex items-center justify-between">
            <div>
                <div class="font-semibold">{{ $b['name'] ?? 'backup' }}</div>
                <div class="text-xs text-base/50">
                    {{ \Illuminate\Support\Number::fileSize($b['bytes'] ?? 0) }}
                    @if(!empty($b['created_at'])) · {{ \Carbon\Carbon::parse($b['created_at'])->diffForHumans() }} @endif
                    @if(!empty($b['is_locked'])) · <span class="text-warning">Locked</span> @endif
                </div>
            </div>
            <div class="flex gap-2">
                <x-button.secondary wire:click="restore('{{ $b['uuid'] }}')"
                    wire:confirm="Restore this backup? Current files may be overwritten.">
                    <x-ri-history-line class="size-4" /> Restore
                </x-button.secondary>
                <x-button.danger wire:click="delete('{{ $b['uuid'] }}')"
                    wire:confirm="Delete backup permanently?">
                    <x-ri-delete-bin-line class="size-4" />
                </x-button.danger>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-base/50 text-sm">No backups yet.</div>
        @endforelse
    </div>
</div>

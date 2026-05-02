<div>
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                <x-ri-global-line class="size-5" />
            </div>
            <h2 class="text-xl font-semibold">Network Allocations</h2>
        </div>
        <x-button.primary wire:click="createNew">
            <x-loading target="createNew" />
            <x-ri-add-line class="size-4" />
            <span wire:loading.remove wire:target="createNew">Add Allocation</span>
        </x-button.primary>
    </div>

    <div class="bg-background-secondary border border-neutral rounded-lg overflow-hidden">
        @forelse($allocations as $a)
        <div class="p-4 border-b border-neutral last:border-0 flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="font-mono">{{ $a['ip_alias'] ?: $a['ip'] }}:{{ $a['port'] }}</span>
                    @if(!empty($a['is_default']))
                        <span class="text-xs bg-success/20 text-success px-2 py-0.5 rounded-full">Primary</span>
                    @endif
                </div>
                @if(!empty($a['notes']))
                <div class="text-xs text-base/50 mt-1">{{ $a['notes'] }}</div>
                @endif
            </div>
            <div class="flex gap-2 shrink-0">
                @if(empty($a['is_default']))
                <x-button.secondary wire:click="setPrimary({{ $a['id'] }})">
                    Set Primary
                </x-button.secondary>
                <x-button.danger wire:click="delete({{ $a['id'] }})" wire:confirm="Remove allocation?">
                    <x-ri-delete-bin-line class="size-4" />
                </x-button.danger>
                @endif
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-base/50 text-sm">No allocations.</div>
        @endforelse
    </div>
</div>

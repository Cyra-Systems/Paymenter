<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
            <x-ri-settings-3-line class="size-5" />
        </div>
        <h2 class="text-xl font-semibold">Startup Variables</h2>
    </div>

    <div class="bg-background-secondary border border-neutral rounded-lg overflow-hidden">
        @forelse($variables as $v)
        @php $key = $v['env_variable']; $editable = $v['is_editable'] ?? $v['user_editable'] ?? false; @endphp
        <div class="p-4 border-b border-neutral last:border-0">
            <div class="flex items-center justify-between mb-1">
                <div>
                    <span class="font-semibold">{{ $v['name'] }}</span>
                    <code class="text-xs text-base/50 ml-2">{{ $key }}</code>
                </div>
                @if($editable)
                <x-button.primary wire:click="save('{{ $key }}')">
                    <x-loading target="save('{{ $key }}')" />
                    <span wire:loading.remove wire:target="save('{{ $key }}')">Save</span>
                </x-button.primary>
                @else
                <span class="text-xs text-base/50">Read-only</span>
                @endif
            </div>
            @if(!empty($v['description']))
            <p class="text-xs text-base/50 mb-2">{{ $v['description'] }}</p>
            @endif
            <input type="text"
                wire:model="values.{{ $key }}"
                @disabled(!$editable)
                class="w-full bg-background border border-neutral rounded-md px-3 py-2 font-mono text-sm" />
        </div>
        @empty
        <div class="p-8 text-center text-base/50 text-sm">No startup variables.</div>
        @endforelse
    </div>
</div>

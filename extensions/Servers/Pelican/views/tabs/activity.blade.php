<div>
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                <x-ri-pulse-line class="size-5" />
            </div>
            <h2 class="text-xl font-semibold">Activity</h2>
        </div>
        <button wire:click="refresh" class="text-sm text-primary-500 hover:underline flex items-center gap-1">
            <x-ri-refresh-line class="size-4" /> Refresh
        </button>
    </div>

    <div class="bg-background-secondary border border-neutral rounded-lg overflow-hidden">
        @forelse($entries as $e)
        <div class="p-3 border-b border-neutral last:border-0 text-sm flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="font-mono text-xs text-base/70">{{ $e['event'] ?? '?' }}</div>
                @if(!empty($e['ip']))
                <div class="text-xs text-base/50">{{ $e['ip'] }}</div>
                @endif
            </div>
            <div class="text-xs text-base/50 shrink-0">
                @if(!empty($e['timestamp']))
                {{ \Carbon\Carbon::parse($e['timestamp'])->diffForHumans() }}
                @endif
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-base/50 text-sm">No activity yet.</div>
        @endforelse
    </div>
</div>

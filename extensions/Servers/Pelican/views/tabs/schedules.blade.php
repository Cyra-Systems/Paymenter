<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
            <x-ri-calendar-schedule-line class="size-5" />
        </div>
        <h2 class="text-xl font-semibold">Schedules</h2>
    </div>

    <form wire:submit.prevent="create" class="bg-background-secondary border border-neutral p-4 rounded-lg mb-4 grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
        <div class="md:col-span-2">
            <label class="text-xs text-base/70">Name</label>
            <input type="text" wire:model="newName" required
                class="w-full bg-background border border-neutral rounded-md px-2 py-1.5 mt-1 text-sm" />
        </div>
        <div><label class="text-xs text-base/70">Min</label><input type="text" wire:model="newMinute" class="w-full bg-background border border-neutral rounded-md px-2 py-1.5 mt-1 text-sm font-mono" /></div>
        <div><label class="text-xs text-base/70">Hour</label><input type="text" wire:model="newHour" class="w-full bg-background border border-neutral rounded-md px-2 py-1.5 mt-1 text-sm font-mono" /></div>
        <div><label class="text-xs text-base/70">DoW</label><input type="text" wire:model="newDayOfWeek" class="w-full bg-background border border-neutral rounded-md px-2 py-1.5 mt-1 text-sm font-mono" /></div>
        <div><label class="text-xs text-base/70">DoM</label><input type="text" wire:model="newDayOfMonth" class="w-full bg-background border border-neutral rounded-md px-2 py-1.5 mt-1 text-sm font-mono" /></div>
        <div class="md:col-span-6 flex justify-between items-center">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="newOnlyWhenOnline" /> Only when server is online
            </label>
            <x-button.primary type="submit">Create Schedule</x-button.primary>
        </div>
    </form>

    <div class="bg-background-secondary border border-neutral rounded-lg overflow-hidden">
        @forelse($schedules as $s)
        <div class="p-4 border-b border-neutral last:border-0 flex items-center justify-between">
            <div>
                <div class="font-semibold">{{ $s['name'] ?? '?' }}</div>
                <div class="text-xs text-base/50 font-mono">
                    {{ $s['cron']['minute'] ?? $s['minute'] ?? '*' }}
                    {{ $s['cron']['hour'] ?? $s['hour'] ?? '*' }}
                    {{ $s['cron']['day_of_month'] ?? $s['day_of_month'] ?? '*' }}
                    *
                    {{ $s['cron']['day_of_week'] ?? $s['day_of_week'] ?? '*' }}
                </div>
            </div>
            <div class="flex gap-2">
                <x-button.secondary wire:click="execute({{ $s['id'] }})">
                    <x-ri-play-line class="size-4" /> Run
                </x-button.secondary>
                <x-button.danger wire:click="delete({{ $s['id'] }})" wire:confirm="Delete schedule?">
                    <x-ri-delete-bin-line class="size-4" />
                </x-button.danger>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-base/50 text-sm">No schedules yet.</div>
        @endforelse
    </div>
</div>

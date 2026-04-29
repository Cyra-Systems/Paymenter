<x-pelican::layout :service="$service" :settings="$settings" view="console">
    <div wire:poll.{{ $fastPolling ? '3s' : '30s' }}="checkStatus"
         wire:key="{{ $fastPolling ? 'fast' : 'slow' }}">

        <div class="flex items-center gap-3 mb-6">
            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                <x-ri-terminal-box-fill class="size-5" />
            </div>
            <h2 class="text-xl font-semibold">Console</h2>
        </div>

        @if($panelServer)
        @php
            $state     = $serverResources['current_state'] ?? 'offline';
            $isRunning = $state === 'running';
            $isBusy    = in_array($state, ['starting', 'stopping']);
        @endphp

        <div class="bg-background-secondary border border-neutral p-6 rounded-lg">

            {{-- Status indicator --}}
            <div class="flex items-center gap-3 mb-6">
                @if($isRunning)
                    <div class="relative inline-flex size-5">
                        <span class="absolute inset-0 rounded-full bg-success/20 animate-ping"></span>
                        <span class="relative flex items-center justify-center size-5 p-1 rounded-full text-success bg-success/20">
                            <x-ri-circle-fill />
                        </span>
                    </div>
                    <span class="font-semibold text-success">Running</span>
                @elseif($isBusy)
                    <span class="flex items-center justify-center size-5 p-1 rounded-full text-warning bg-warning/20">
                        <x-ri-loader-4-line />
                    </span>
                    <span class="font-semibold text-warning capitalize">{{ ucfirst($state) }}</span>
                @else
                    <span class="flex items-center justify-center size-5 p-1 rounded-full text-inactive bg-inactive/20">
                        <x-ri-forbid-fill />
                    </span>
                    <span class="font-semibold text-inactive">Offline</span>
                @endif
            </div>

            {{-- Power buttons --}}
            <div class="flex flex-wrap gap-3">
                <x-button.primary
                    wire:click="power('start')"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    :disabled="$isRunning || $isBusy"
                    class="disabled:cursor-not-allowed">
                    <x-loading target="power('start')" />
                    <x-ri-play-fill class="size-4" />
                    <span wire:loading.remove wire:target="power('start')">Start</span>
                </x-button.primary>

                <x-button.secondary
                    wire:click="power('stop')"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    :disabled="! $isRunning"
                    class="disabled:cursor-not-allowed">
                    <x-loading target="power('stop')" />
                    <x-ri-stop-fill class="size-4" />
                    <span wire:loading.remove wire:target="power('stop')">Stop</span>
                </x-button.secondary>

                <x-button.secondary
                    wire:click="power('restart')"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    :disabled="! $isRunning"
                    class="disabled:cursor-not-allowed">
                    <x-loading target="power('restart')" />
                    <x-ri-loop-left-line class="size-4" />
                    <span wire:loading.remove wire:target="power('restart')">Restart</span>
                </x-button.secondary>

                <x-button.danger
                    wire:click="power('kill')"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    :disabled="! $isRunning"
                    class="disabled:cursor-not-allowed">
                    <x-loading target="power('kill')" />
                    <x-ri-shut-down-line class="size-4" />
                    <span wire:loading.remove wire:target="power('kill')">Kill</span>
                </x-button.danger>
            </div>

            {{-- Reinstall — only if enabled per product settings --}}
            @if(!empty($settings['show_reinstall']))
            <div class="mt-8 pt-6 border-t border-neutral">
                <h3 class="text-base font-semibold mb-1">Danger Zone</h3>
                <p class="text-sm text-base/50 mb-4">Reinstalling wipes all server data and re-runs the installation script.</p>
                <x-button.danger
                    wire:click="reinstall"
                    wire:confirm="Are you sure you want to reinstall? All server data will be permanently deleted."
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed">
                    <x-loading target="reinstall" />
                    <x-ri-restart-line class="size-4" />
                    <span wire:loading.remove wire:target="reinstall">Reinstall</span>
                </x-button.danger>
            </div>
            @endif

        </div>

        @else
        <div class="bg-background-secondary border border-neutral p-8 rounded-lg text-center">
            <x-ri-server-line class="size-12 mx-auto text-base/30 mb-4" />
            <p class="text-base/50">Server is not ready yet. Please check back shortly.</p>
        </div>
        @endif

    </div>
</x-pelican::layout>

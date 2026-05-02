<div wire:poll.{{ $fastPolling ? '3s' : '30s' }}="checkStatus"
     wire:key="pelican-overview-{{ $fastPolling ? 'fast' : 'slow' }}">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                <x-ri-server-fill class="size-5" />
            </div>
            <h2 class="text-xl font-semibold">Server Overview</h2>
        </div>

        @if($subdomain)
        <a href="{{ $subdomain }}" target="_blank" rel="noopener"
           class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
            <x-ri-external-link-line class="size-4" />
            Open App
        </a>
        @endif
    </div>

    @if($panelServer)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div class="bg-background-secondary border border-neutral p-4 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Status</h3>

            @php
                $state       = $serverResources['current_state'] ?? 'offline';
                $isSuspended = $serverResources['is_suspended']   ?? false;
            @endphp

            <div class="flex items-center gap-3 mb-6">
                @if($state === 'running')
                    <div class="relative inline-flex size-5">
                        <span class="absolute inset-0 rounded-full bg-success/20 animate-ping"></span>
                        <span class="relative flex items-center justify-center size-5 p-1 rounded-full text-success bg-success/20">
                            <x-ri-circle-fill />
                        </span>
                    </div>
                    <span class="font-semibold text-success">Running</span>
                @elseif(in_array($state, ['starting', 'stopping']))
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

                @if($isSuspended)
                    <span class="text-xs bg-warning/20 text-warning px-2 py-0.5 rounded-full">Suspended</span>
                @endif
            </div>

            <div class="space-y-3 text-sm">
                @if($eggName)
                <div class="flex flex-col">
                    <span class="text-base/50">Environment</span>
                    <span class="font-semibold">{{ $eggName }}</span>
                </div>
                @endif

                <div class="flex flex-col">
                    <span class="text-base/50">Server Name</span>
                    <span class="font-semibold">{{ $panelServer['name'] }}</span>
                </div>

                @if($alloc)
                <div class="flex flex-col">
                    <span class="text-base/50">Allocation</span>
                    <span class="font-semibold font-mono">{{ $alloc['ip'] }}:{{ $alloc['port'] }}</span>
                </div>
                @endif

                @if($subdomain)
                <div class="flex flex-col">
                    <span class="text-base/50">Your URL</span>
                    <a href="{{ $subdomain }}" target="_blank" rel="noopener"
                       class="font-semibold text-primary-500 hover:underline break-all">
                        {{ $subdomain }}
                    </a>
                </div>
                @endif
            </div>
        </div>

        @if($serverResources && $state === 'running')
        <div class="bg-background-secondary border border-neutral p-4 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Resources</h3>

            @php
                $res       = $serverResources['resources'] ?? [];
                $cpu       = round($res['cpu_absolute'] ?? 0, 1);
                $memBytes  = $res['memory_bytes'] ?? 0;
                $diskBytes = $res['disk_bytes'] ?? 0;
            @endphp

            <div class="space-y-5">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-base/50">CPU</span>
                        <span class="font-semibold">{{ $cpu }}%</span>
                    </div>
                    <div class="w-full bg-background rounded-full h-2 border border-neutral overflow-hidden">
                        <div class="h-2 rounded-full bg-info/60 border border-info"
                             style="width: {{ min($cpu, 100) }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-base/50">Memory</span>
                        <span class="font-semibold">{{ \Illuminate\Support\Number::fileSize($memBytes) }}</span>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-base/50">Disk</span>
                        <span class="font-semibold">{{ \Illuminate\Support\Number::fileSize($diskBytes) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="bg-background-secondary border border-neutral p-4 rounded-lg flex items-center justify-center min-h-32">
            <p class="text-base/50 text-sm">Start the server to see resource usage.</p>
        </div>
        @endif

    </div>
    @else
    <div class="bg-background-secondary border border-neutral p-8 rounded-lg text-center">
        <x-ri-server-line class="size-12 mx-auto text-base/30 mb-4" />
        <p class="text-base/50">Server is being provisioned. Please check back shortly.</p>
    </div>
    @endif

</div>

<div class="container mt-14 space-y-4">
    <x-navigation.breadcrumb />

    <div class="flex flex-row items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">{{ __('navigation.domains') }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('domains.register') }}" wire:navigate
                class="px-3 py-2 rounded-lg bg-primary text-white text-sm">Register</a>
            <a href="{{ route('domains.transfer') }}" wire:navigate
                class="px-3 py-2 rounded-lg border border-neutral text-sm">Transfer</a>
        </div>
    </div>

    @forelse ($domains as $domain)
        <a href="{{ route('domains.show', $domain) }}" wire:navigate>
            <div class="bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg mb-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <div class="bg-secondary/10 p-2 rounded-lg">
                            <x-ri-global-line class="size-5 text-secondary" />
                        </div>
                        <span class="font-medium">{{ $domain->fqdn }}</span>
                    </div>
                    <div class="text-xs uppercase opacity-70">{{ $domain->status }}</div>
                </div>
                <div class="text-sm flex gap-1 opacity-80">
                    @if ($domain->expires_at)
                        Expires {{ $domain->expires_at->format('M d, Y') }}
                    @else
                        No expiry recorded yet
                    @endif
                    @if ($domain->activeBinding)
                        — bound to service #{{ $domain->activeBinding->service_id }} ({{ $domain->activeBinding->hostname }})
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="bg-background-secondary border border-neutral p-4 rounded-lg">
            <p class="text-sm">You don't have any domains yet.</p>
        </div>
    @endforelse

    {{ $domains->links() }}
</div>

<div class="bg-background-secondary border border-neutral p-4 rounded-md mt-2">
    <h3 class="font-semibold text-base mb-3">Choose how to set up the domain</h3>

    <div class="flex flex-wrap gap-2 mb-4">
        @foreach ($this->allowedDomainPaths() as $path)
            <button type="button" wire:click="pickDomainPath('{{ $path }}')"
                class="px-3 py-2 text-sm rounded-md border {{ $domainPath === $path ? 'bg-primary text-white border-primary' : 'border-neutral' }}">
                @switch($path)
                    @case('custom')
                        Use custom domain
                    @break
                    @case('forward')
                        Forward an existing domain
                    @break
                    @case('subdomain')
                        Use a free subdomain
                    @break
                @endswitch
            </button>
        @endforeach
    </div>

    @if ($domainPath === 'custom')
        <div class="space-y-2">
            <p class="text-sm opacity-80">Type the SLD and TLD for the domain you want to register or attach.</p>
            <div class="flex flex-col md:flex-row gap-2">
                <input type="text" class="flex-1 bg-transparent border border-neutral rounded px-3 py-2"
                    wire:model.blur="domainSld" placeholder="example" />
                <span class="self-center">.</span>
                <input type="text" class="w-32 bg-transparent border border-neutral rounded px-3 py-2"
                    wire:model.blur="domainTld" placeholder="com" />
                <button type="button" wire:click="checkDomainAvailability"
                    class="px-3 py-2 rounded-md border border-neutral text-sm">Check</button>
            </div>
            @if ($domainAvailability)
                <p class="text-sm">{{ $domainAvailability }}</p>
            @endif
        </div>
    @elseif ($domainPath === 'forward')
        <div class="space-y-2">
            <p class="text-sm opacity-80">Enter the domain you already own. Point its A/AAAA record to
                <code>{{ $this->proxyTargetHost() ?: 'your.proxy.host' }}</code> so traffic reaches our reverse proxy.
            </p>
            <input type="text" class="w-full bg-transparent border border-neutral rounded px-3 py-2"
                wire:model="domainForwardHostname" placeholder="example.com" />
            <input type="text" class="w-full bg-transparent border border-neutral rounded px-3 py-2"
                wire:model="domainForwardTarget" placeholder="https://target-url.example/path (optional)" />
        </div>
    @elseif ($domainPath === 'subdomain')
        <div class="space-y-2">
            <p class="text-sm opacity-80">Pick a prefix. Your service will be reachable at
                <code>&lt;prefix&gt;.{{ $this->subdomainBase() ?: 'your.base' }}</code>.</p>
            <div class="flex flex-col md:flex-row gap-2 items-center">
                <input type="text" class="flex-1 bg-transparent border border-neutral rounded px-3 py-2"
                    wire:model.blur="domainSubdomainPrefix" placeholder="myapp" />
                <span class="opacity-70">.{{ $this->subdomainBase() }}</span>
                <button type="button" wire:click="checkSubdomainAvailability"
                    class="px-3 py-2 rounded-md border border-neutral text-sm">Check</button>
            </div>
            @if ($domainSubdomainStatus)
                <p class="text-sm">{{ $domainSubdomainStatus }}</p>
            @endif
        </div>
    @else
        <p class="text-sm opacity-70">Select an option above.</p>
    @endif
</div>

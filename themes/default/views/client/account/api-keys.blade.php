<div class="container mt-14">
    <x-navigation.breadcrumb />
    <div class="px-2 flex flex-col gap-4">

        {{-- One-time token banner --}}
        @if ($newToken)
        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
            <h5 class="text-lg font-bold pb-2 text-yellow-600 dark:text-yellow-400">{{ __('Copy your API token now') }}</h5>
            <p class="text-sm text-primary-100 mb-3">{{ __('This token will not be shown again. Store it securely.') }}</p>
            <div class="flex flex-row gap-2 items-center">
                <code class="flex-1 bg-background rounded px-3 py-2 text-sm font-mono break-all select-all">{{ $newToken }}</code>
                <button
                    x-data="{ copied: false }"
                    x-on:click="navigator.clipboard.writeText('{{ $newToken }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    class="shrink-0 px-3 py-2 rounded bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-700 dark:text-yellow-300 text-sm font-medium transition-colors"
                    x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy') }}'"
                ></button>
                <x-button.primary wire:click="dismissToken" class="!w-fit text-sm shrink-0">
                    {{ __('Done') }}
                </x-button.primary>
            </div>
        </div>
        @endif

        {{-- Create form --}}
        <div class="bg-background-secondary rounded-lg p-4">
            <h5 class="text-lg font-bold pb-3">{{ __('Create API Key') }}</h5>
            <div class="grid grid-cols-2 gap-4">
                <x-form.input name="name" type="text" :label="__('Key Name')"
                    :placeholder="__('e.g. My Reseller App')" wire:model="name" required />

                <x-form.input name="rateLimit" type="number" :label="__('Rate Limit (requests/min)')"
                    :placeholder="__('Leave blank for unlimited')" wire:model="rateLimit" />

                <div class="col-span-2">
                    <x-form.input name="ipAddresses" type="text" :label="__('IP Whitelist')"
                        :placeholder="__('Comma-separated IPs or CIDRs — leave blank to allow any')"
                        wire:model="ipAddresses" />
                    @error('ipAddresses')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @if (!empty($availablePermissions))
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-2">{{ __('Permissions') }}</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-2">
                        @foreach ($availablePermissions as $section => $perms)
                            <p class="col-span-full text-xs font-semibold uppercase tracking-wide text-primary-400 mt-2 first:mt-0">
                                {{ ucfirst($section) }}
                            </p>
                            @foreach ($perms as $key => $label)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="selectedPermissions"
                                    value="{{ $section }}.{{ $key }}"
                                    class="rounded border-neutral text-primary focus:ring-primary" />
                                <span class="text-sm">{{ $label }}</span>
                            </label>
                            @endforeach
                        @endforeach
                    </div>
                    <p class="text-xs text-primary-400 mt-2">{{ __('Leave all unchecked to grant full access.') }}</p>
                </div>
                @endif
            </div>
            <x-button.primary wire:click="create" class="w-full mt-4">
                {{ __('Create API Key') }}
            </x-button.primary>
        </div>

        {{-- Key list --}}
        <div class="bg-background-secondary rounded-lg p-4">
            <h5 class="text-lg font-bold pb-3">{{ __('Your API Keys') }}</h5>
            @forelse ($keys as $key)
            <div class="flex flex-row items-start justify-between py-3 border-b border-base/50 last:border-0 gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="font-medium">{{ $key->name }}</p>
                        @if ($key->enabled)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-500/15 text-green-600 dark:text-green-400">{{ __('Active') }}</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-neutral-500/15 text-neutral-500">{{ __('Disabled') }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-primary-400 mt-0.5">
                        {{ __('Created') }}: {{ $key->created_at->diffForHumans() }}
                        &bull;
                        {{ __('Last used') }}: {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : __('Never') }}
                        &bull;
                        {{ __('Rate limit') }}: {{ $key->rate_limit ? $key->rate_limit . ' req/min' : __('Unlimited') }}
                    </p>
                    <p class="text-xs text-primary-400 mt-0.5">
                        {{ __('IPs') }}: {{ $key->ip_addresses ? implode(', ', $key->ip_addresses) : __('Any') }}
                    </p>
                    <p class="text-xs text-primary-400 mt-0.5">
                        @if ($key->permissions)
                            {{ collect($key->permissions)->map(fn($p) => $permissionLabels[$p] ?? $p)->implode(', ') }}
                        @else
                            {{ __('All permissions') }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-col gap-2 shrink-0">
                    <x-button.secondary wire:click="toggle({{ $key->id }})" class="text-sm !w-fit">
                        {{ $key->enabled ? __('Disable') : __('Enable') }}
                    </x-button.secondary>
                    <x-button.primary
                        x-on:click="$store.confirmation.confirm({
                            title: '{{ __('Revoke API Key') }}',
                            message: '{{ __('Are you sure? This cannot be undone.') }}',
                            confirmText: '{{ __('Revoke') }}',
                            cancelText: '{{ __('Cancel') }}',
                            callback: () => $wire.revoke({{ $key->id }})
                        })"
                        class="text-sm !w-fit">
                        {{ __('Revoke') }}
                    </x-button.primary>
                </div>
            </div>
            @empty
            <p class="text-primary-400 text-sm">{{ __('No API keys yet.') }}</p>
            @endforelse
        </div>

    </div>
</div>

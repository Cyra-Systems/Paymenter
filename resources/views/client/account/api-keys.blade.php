<div>
    {{-- New token banner --}}
    @if ($newToken)
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4">
            <p class="mb-2 text-sm font-semibold text-green-800">{{ __('Your new API key — copy it now, it will not be shown again:') }}</p>
            <code class="block break-all rounded bg-green-100 px-3 py-2 font-mono text-sm text-green-900">{{ $newToken }}</code>
            <button wire:click="dismissToken" class="mt-3 text-xs text-green-700 underline">{{ __('I have copied it, dismiss') }}</button>
        </div>
    @endif

    {{-- Create form --}}
    <div class="mb-8 rounded-lg border bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold">{{ __('Create API Key') }}</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                <input wire:model="name" type="text" placeholder="e.g. My Reseller App"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Rate Limit') }} <span class="text-gray-400">({{ __('requests/minute, leave blank for unlimited') }})</span></label>
                <input wire:model="rateLimit" type="number" min="1" max="10000" placeholder="e.g. 60"
                    class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('rateLimit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('IP Whitelist') }} <span class="text-gray-400">({{ __('comma-separated, leave blank to allow all') }})</span></label>
                <input wire:model="ipAddresses" type="text" placeholder="e.g. 1.2.3.4, 5.6.7.8"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('ipAddresses') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Permissions') }} <span class="text-gray-400">({{ __('leave all unchecked for full access') }})</span></label>
                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($availablePermissions as $group => $actions)
                        @foreach ($actions as $action => $label)
                            @php $permKey = 'user.' . $group . '.' . $action; @endphp
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="selectedPermissions" value="{{ $permKey }}"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $label }}
                                <span class="text-xs text-gray-400">({{ $permKey }})</span>
                            </label>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <button wire:click="create"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                {{ __('Create Key') }}
            </button>
        </div>
    </div>

    {{-- Existing keys --}}
    <div class="rounded-lg border bg-white shadow-sm">
        <h2 class="border-b px-6 py-4 text-lg font-semibold">{{ __('Your API Keys') }}</h2>

        @forelse ($keys as $key)
            <div class="flex items-start justify-between border-b px-6 py-4 last:border-b-0">
                <div class="space-y-1">
                    <p class="font-medium">{{ $key->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ __('Created') }}: {{ $key->created_at->toIso8601String() }}
                        &nbsp;&bull;&nbsp;
                        {{ __('Last used') }}: {{ $key->last_used_at?->toIso8601String() ?? __('Never') }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ __('Rate limit') }}: {{ $key->rate_limit ? $key->rate_limit . ' req/min' : __('Unlimited') }}
                        &nbsp;&bull;&nbsp;
                        {{ __('Permissions') }}: {{ $key->permissions ? implode(', ', $key->permissions) : __('All') }}
                    </p>
                </div>
                <button wire:click="revoke({{ $key->id }})"
                    wire:confirm="{{ __('Are you sure you want to revoke this API key? This cannot be undone.') }}"
                    class="ml-4 text-sm font-medium text-red-600 hover:text-red-800">
                    {{ __('Revoke') }}
                </button>
            </div>
        @empty
            <p class="px-6 py-6 text-sm text-gray-500">{{ __('No API keys yet. Create one above.') }}</p>
        @endforelse
    </div>
</div>

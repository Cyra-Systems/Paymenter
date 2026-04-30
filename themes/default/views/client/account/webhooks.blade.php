<div class="container mt-14">
    <x-navigation.breadcrumb />
    <div class="px-2 flex flex-col gap-4">

        @if ($newSecret)
        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
            <h5 class="text-lg font-bold pb-2 text-yellow-600 dark:text-yellow-400">{{ __('Copy your webhook secret now') }}</h5>
            <p class="text-sm text-primary-100 mb-3">{{ __('This secret will not be shown again. Use it to verify the X-Webhook-Signature header on incoming requests.') }}</p>
            <div class="flex flex-row gap-2 items-center">
                <code class="flex-1 bg-background rounded px-3 py-2 text-sm font-mono break-all select-all">{{ $newSecret }}</code>
                <x-button.primary wire:click="dismissSecret" class="!w-fit text-sm shrink-0">
                    {{ __('Done') }}
                </x-button.primary>
            </div>
        </div>
        @endif

        <div class="bg-background-secondary rounded-lg p-4">
            <h5 class="text-lg font-bold pb-3">{{ __('Add Webhook') }}</h5>
            <div class="flex flex-col gap-4">
                <x-form.input name="url" type="url" :label="__('Endpoint URL')"
                    :placeholder="__('https://your-server.com/webhook')" wire:model="url" required />

                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('Events') }}</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach ($availableEvents as $event => $label)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="selectedEvents" value="{{ $event }}"
                                class="rounded border-neutral text-primary focus:ring-primary" />
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <x-button.primary wire:click="create" class="w-full mt-4">
                {{ __('Create Webhook') }}
            </x-button.primary>
        </div>

        <div class="bg-background-secondary rounded-lg p-4">
            <h5 class="text-lg font-bold pb-3">{{ __('Your Webhooks') }}</h5>
            @forelse ($webhooks as $webhook)
            <div class="py-3 border-b border-base/50 last:border-0">
                <div class="flex flex-row items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full shrink-0 {{ $webhook->enabled ? 'bg-green-500' : 'bg-neutral-400' }}"></span>
                            <p class="font-medium text-sm break-all">{{ $webhook->url }}</p>
                        </div>
                        <p class="text-xs text-primary-400 mt-1 ml-4">{{ implode(', ', $webhook->events) }}</p>
                        @if ($webhook->last_called_at)
                        <p class="text-xs text-primary-400 ml-4">{{ __('Last called') }}: {{ $webhook->last_called_at->diffForHumans() }}</p>
                        @endif
                    </div>
                    <div class="flex flex-row gap-2 shrink-0">
                        <x-button.primary wire:click="sendTest({{ $webhook->id }})" class="text-xs !w-fit">
                            {{ __('Test') }}
                        </x-button.primary>
                        <x-button.primary wire:click="toggle({{ $webhook->id }})" class="text-xs !w-fit">
                            {{ $webhook->enabled ? __('Disable') : __('Enable') }}
                        </x-button.primary>
                        <x-button.primary
                            x-on:click="$store.confirmation.confirm({
                                title: '{{ __('Delete Webhook') }}',
                                message: '{{ __('Are you sure? This cannot be undone.') }}',
                                confirmText: '{{ __('Delete') }}',
                                cancelText: '{{ __('Cancel') }}',
                                callback: () => $wire.delete({{ $webhook->id }})
                            })"
                            class="text-xs !w-fit">
                            {{ __('Delete') }}
                        </x-button.primary>
                    </div>
                </div>
            </div>
            @empty
            <p class="text-primary-400 text-sm">{{ __('No webhooks configured yet.') }}</p>
            @endforelse
        </div>

    </div>
</div>

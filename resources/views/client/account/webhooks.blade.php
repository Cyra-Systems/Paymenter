<div>
    {{-- New secret banner --}}
    @if ($newSecret)
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4">
            <p class="mb-2 text-sm font-semibold text-green-800">{{ __('Your webhook signing secret — copy it now, it will not be shown again:') }}</p>
            <code class="block break-all rounded bg-green-100 px-3 py-2 font-mono text-sm text-green-900">{{ $newSecret }}</code>
            <p class="mt-2 text-xs text-green-700">{{ __('Verify incoming requests by computing') }} <code>sha256=HMAC_SHA256(secret, raw_body)</code> {{ __('and comparing it to the') }} <code>X-Webhook-Signature</code> {{ __('header.') }}</p>
            <button wire:click="dismissSecret" class="mt-3 text-xs text-green-700 underline">{{ __('I have copied it, dismiss') }}</button>
        </div>
    @endif

    {{-- Create form --}}
    <div class="mb-8 rounded-lg border bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold">{{ __('Add Webhook') }}</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Endpoint URL') }}</label>
                <input wire:model="url" type="url" placeholder="https://your-server.com/webhook"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Events to subscribe to') }}</label>
                <div class="mt-2 space-y-2">
                    @foreach ($availableEvents as $event => $label)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="selectedEvents" value="{{ $event }}"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            {{ $label }}
                            <span class="text-xs text-gray-400">({{ $event }})</span>
                        </label>
                    @endforeach
                </div>
                @error('selectedEvents') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button wire:click="create"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                {{ __('Add Webhook') }}
            </button>
        </div>
    </div>

    {{-- Existing webhooks --}}
    <div class="rounded-lg border bg-white shadow-sm">
        <h2 class="border-b px-6 py-4 text-lg font-semibold">{{ __('Your Webhooks') }}</h2>

        @forelse ($webhooks as $webhook)
            <div class="flex items-start justify-between border-b px-6 py-4 last:border-b-0">
                <div class="space-y-1">
                    <p class="break-all font-mono text-sm">{{ $webhook->url }}</p>
                    <p class="text-xs text-gray-500">
                        {{ __('Events') }}: {{ implode(', ', $webhook->events) }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ __('Status') }}: <span class="font-medium {{ $webhook->enabled ? 'text-green-600' : 'text-gray-400' }}">{{ $webhook->enabled ? __('Enabled') : __('Disabled') }}</span>
                        &nbsp;&bull;&nbsp;
                        {{ __('Last called') }}: {{ $webhook->last_called_at?->toIso8601String() ?? __('Never') }}
                    </p>
                </div>
                <div class="ml-4 flex shrink-0 gap-3">
                    <button wire:click="sendTest({{ $webhook->id }})" class="text-sm text-indigo-600 hover:text-indigo-800">
                        {{ __('Test') }}
                    </button>
                    <button wire:click="toggle({{ $webhook->id }})" class="text-sm text-gray-600 hover:text-gray-800">
                        {{ $webhook->enabled ? __('Disable') : __('Enable') }}
                    </button>
                    <button wire:click="delete({{ $webhook->id }})"
                        wire:confirm="{{ __('Are you sure you want to delete this webhook?') }}"
                        class="text-sm text-red-600 hover:text-red-800">
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        @empty
            <p class="px-6 py-6 text-sm text-gray-500">{{ __('No webhooks yet. Add one above.') }}</p>
        @endforelse
    </div>

    {{-- Payload format reference --}}
    <div class="mt-6 rounded-lg border bg-gray-50 p-6">
        <h3 class="mb-3 text-sm font-semibold text-gray-700">{{ __('Payload format') }}</h3>
        <pre class="overflow-x-auto rounded bg-white p-4 text-xs text-gray-700">{
  "event": "invoice.paid",
  "timestamp": "2025-04-30T14:23:00+00:00",
  "data": { ... }
}</pre>
        <p class="mt-3 text-xs text-gray-500">{{ __('Verify authenticity: compute') }} <code class="bg-white px-1">sha256=HMAC_SHA256(secret, raw_body)</code> {{ __('and compare to the') }} <code class="bg-white px-1">X-Webhook-Signature</code> {{ __('header.') }}</p>
    </div>
</div>

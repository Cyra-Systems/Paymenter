<div class="domain-picker space-y-4 rounded-md border border-gray-200 dark:border-gray-700 p-4">
    <h3 class="text-base font-semibold">Domain</h3>

    @if (empty($this->options))
        <p class="text-sm text-gray-500">This product doesn't have any domain options enabled.</p>
    @else
        <div>
            <label class="text-sm font-medium">What do you want to do?</label>
            <select wire:model.live="action" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                <option value="">— Choose —</option>
                @foreach ($this->options as $opt)
                    <option value="{{ $opt }}">
                        @switch($opt)
                            @case('register') Register a new domain @break
                            @case('transfer') Transfer in an existing domain @break
                            @case('custom') Use a domain I already own @break
                            @case('subdomain') Use a subdomain of one of my domains @break
                            @case('forward') Forward a domain to a URL @break
                        @endswitch
                    </option>
                @endforeach
            </select>
        </div>

        @if ($action)
            <div>
                <label class="text-sm font-medium">Domain</label>
                <div class="mt-1 flex gap-2">
                    @if ($action === 'subdomain')
                        <input wire:model.defer="domain" type="text" placeholder="myshop"
                               class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                        <select wire:model.defer="parentDomainId"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                            @foreach ($parentDomains as $id => $d)
                                <option value="{{ $id }}">.{{ $d }}</option>
                            @endforeach
                        </select>
                    @else
                        <input wire:model.defer="domain" type="text" placeholder="example.com"
                               class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                        @if (in_array($action, ['register', 'transfer']))
                            <button type="button" wire:click="checkAvailability"
                                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm text-white">
                                Check
                            </button>
                        @endif
                    @endif
                </div>
                @if ($availabilityMessage)
                    <p class="mt-2 text-sm {{ $availability ? 'text-green-600' : 'text-red-600' }}">
                        {{ $availabilityMessage }}
                    </p>
                @endif
            </div>

            @if ($action === 'transfer')
                <div>
                    <label class="text-sm font-medium">EPP / Auth-info code</label>
                    <input wire:model.defer="authCode" type="text"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                </div>
            @endif

            @if ($action === 'forward')
                <div>
                    <label class="text-sm font-medium">Forward to URL</label>
                    <input wire:model.defer="forwardUrl" type="url" placeholder="https://example.com/landing"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                </div>
            @endif

            @if (in_array($action, ['register', 'transfer']))
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium">Years</label>
                        <input wire:model.defer="period" type="number" min="1" max="10"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800" />
                    </div>
                    <div class="space-y-2 pt-5">
                        <label class="flex items-center gap-2 text-sm">
                            <input wire:model.defer="idProtect" type="checkbox" /> Whois Privacy
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input wire:model.defer="autoRenew" type="checkbox" /> Auto-renew
                        </label>
                    </div>
                </div>
            @endif

            <button type="button" wire:click="save"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">
                Save domain selection
            </button>
        @endif
    @endif
</div>

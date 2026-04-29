<x-proxmox::layout :server="$server" view="settings">
    <div class=" bg-background-secondary border border-neutral rounded-lg">
        <!-- Hostname -->
        <div class="flex items-center justify-between gap-2 p-4">
            <div class="flex flex-col">

                <label class="text-lg font-semibold" for="hostname">
                    Hostname
                </label>
                <p class="text-sm font-semibold text-base/60">
                    Update the hostname of your server.
                </p>
            </div>
            <div class="flex gap-2 items-center">
                <x-form.input id="hostname" name="hostname" type="text" wire:model='hostname' />
                <x-button.primary wire:click="updateHostname" class="!w-fit" wire:loading.attr="disabled">
                    <div role="status" wire:loading wire:target='updateHostname'>
                        <x-ri-loader-5-fill aria-hidden="true" class="size-6 me-2 fill-background animate-spin" />
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div wire:loading.remove wire:target='updateHostname'>
                        Update
                    </div>
                </x-button.primary>
            </div>
        </div>

        <!-- Password reset -->
        <div class="flex items-center justify-between gap-2 p-4">
            <div class="flex flex-col">
                <label class="text-lg font-semibold" for="password">
                    Root Password
                </label>
                <p class="text-sm font-semibold text-base/60">
                    Change the root password of your server.
                </p>
            </div>
            <div class="flex gap-2 items-center">
                <x-button.primary wire:click="requestPasswordReset" class="!w-fit" wire:loading.attr="disabled">
                    <x-loading target="requestPasswordReset" />
                    <span wire:loading.remove wire:target="requestPasswordReset">
                        Request Password Reset
                    </span>
                </x-button.primary>
            </div>
        </div>

        <div class="flex flex-col bg-error/10 border border-error/30 p-4 rounded-b-lg">
            <label class="text-lg font-semibold flex flex-row justify-between items-center gap-8" for="os">
                <div class="flex flex-col gap-2">
                    Reinstall Server
                    <p class="text-sm font-semibold text-base/60">The data on the server will be permanently deleted.
                        This action is irreversible and cannot be undone.</p>
                </div>
                <livewire:proxmox.reinstall :server="$server" />
            </label>
        </div>
    </div>
</x-proxmox::layout>
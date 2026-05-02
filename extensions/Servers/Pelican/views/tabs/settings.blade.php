<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
            <x-ri-tools-line class="size-5" />
        </div>
        <h2 class="text-xl font-semibold">Settings</h2>
    </div>

    @if(!$panelServer)
        <div class="bg-background-secondary border border-neutral p-8 rounded-lg text-center">
            <p class="text-base/50">Server is not ready yet.</p>
        </div>
    @else
    <div class="space-y-4">

        <div class="bg-background-secondary border border-neutral p-4 rounded-lg">
            <h3 class="font-semibold mb-3">Rename</h3>
            <form wire:submit.prevent="rename" class="flex gap-2">
                <input type="text" wire:model="newName" required
                    class="flex-1 bg-background border border-neutral rounded-md px-3 py-2" />
                <x-button.primary type="submit">
                    <x-loading target="rename" />
                    <span wire:loading.remove wire:target="rename">Save</span>
                </x-button.primary>
            </form>
        </div>

        <div class="bg-background-secondary border border-neutral p-4 rounded-lg">
            <h3 class="font-semibold mb-1">SFTP Password</h3>
            <p class="text-sm text-base/50 mb-3">Generate a new SFTP password for this server.</p>
            <x-button.secondary wire:click="regenerateSftp" wire:confirm="Generate a new SFTP password?">
                <x-loading target="regenerateSftp" />
                <x-ri-key-line class="size-4" />
                <span wire:loading.remove wire:target="regenerateSftp">Regenerate Password</span>
            </x-button.secondary>
        </div>

        @if(!empty($settings['show_reinstall']))
        <div class="bg-background-secondary border border-error/30 p-4 rounded-lg">
            <h3 class="font-semibold mb-1 text-error">Danger Zone — Reinstall</h3>
            <p class="text-sm text-base/50 mb-3">Reinstalling wipes all server data and re-runs the installation script.</p>
            <x-button.danger wire:click="reinstall"
                wire:confirm="REINSTALL: All server data will be permanently destroyed. Continue?">
                <x-loading target="reinstall" />
                <x-ri-restart-line class="size-4" />
                <span wire:loading.remove wire:target="reinstall">Reinstall Server</span>
            </x-button.danger>
        </div>
        @endif

    </div>
    @endif
</div>

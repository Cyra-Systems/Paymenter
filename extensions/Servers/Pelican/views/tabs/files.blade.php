<div>
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                <x-ri-folder-line class="size-5" />
            </div>
            <h2 class="text-xl font-semibold">Files</h2>
        </div>
        <button wire:click="refresh" class="text-sm text-primary-500 hover:underline flex items-center gap-1">
            <x-ri-refresh-line class="size-4" /> Refresh
        </button>
    </div>

    @if($error)
    <div class="bg-error/10 border border-error/30 text-error p-3 rounded-md mb-3 text-sm">{{ $error }}</div>
    @endif

    @if(!$panelServer)
        <div class="bg-background-secondary border border-neutral p-8 rounded-lg text-center">
            <p class="text-base/50">Server is not ready yet.</p>
        </div>
    @elseif($editingFile !== null)
        <div class="bg-background-secondary border border-neutral rounded-lg overflow-hidden">
            <div class="flex items-center justify-between bg-background p-3 border-b border-neutral">
                <span class="font-mono text-sm">{{ $editingFile }}</span>
                <div class="flex gap-2">
                    <x-button.primary wire:click="saveFile">
                        <x-loading target="saveFile" />
                        <span wire:loading.remove wire:target="saveFile">Save</span>
                    </x-button.primary>
                    <x-button.secondary wire:click="closeEditor">Close</x-button.secondary>
                </div>
            </div>
            <textarea wire:model="editorContents"
                class="w-full h-96 p-4 font-mono text-sm bg-background text-base focus:outline-none"></textarea>
        </div>
    @else
        <div class="bg-background-secondary border border-neutral rounded-lg overflow-hidden">
            <div class="flex items-center gap-2 p-3 bg-background border-b border-neutral">
                <button wire:click="up" @disabled($directory === '/') class="text-sm text-base/70 hover:text-base disabled:opacity-30">
                    <x-ri-arrow-up-line class="size-4" />
                </button>
                <span class="font-mono text-sm text-base/70">{{ $directory }}</span>
            </div>

            <div class="divide-y divide-neutral">
                @forelse($entries as $e)
                    @php $isDir = ($e['is_file'] ?? false) === false; @endphp
                    <div class="flex items-center justify-between p-3 hover:bg-background/50">
                        <button wire:click="navigate('{{ $e['name'] }}', @json($isDir))"
                            class="flex items-center gap-2 text-left flex-1 min-w-0">
                            @if($isDir)
                                <x-ri-folder-fill class="size-4 text-primary-500 shrink-0" />
                            @else
                                <x-ri-file-line class="size-4 text-base/60 shrink-0" />
                            @endif
                            <span class="truncate">{{ $e['name'] }}</span>
                        </button>
                        <div class="flex items-center gap-3 text-xs text-base/50">
                            @if(! $isDir && isset($e['size']))
                                <span>{{ \Illuminate\Support\Number::fileSize($e['size']) }}</span>
                            @endif
                            <button wire:click="deleteEntry('{{ $e['name'] }}')"
                                wire:confirm="Delete {{ $e['name'] }}?"
                                class="text-error hover:opacity-80">
                                <x-ri-delete-bin-line class="size-4" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-base/50 text-sm">Empty directory.</div>
                @endforelse
            </div>
        </div>
    @endif
</div>

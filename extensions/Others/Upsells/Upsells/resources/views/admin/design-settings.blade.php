<x-filament::page>
    <form wire:submit="save">
        {{ $this->form }}
        <div class="mt-6">
            <button type="submit" style="background-color: #2563eb; color: #fff; font-weight: 600; padding: 0.45rem 1.2rem; border-radius: 0.5rem; transition: background-color 0.2s;">
                Save Design Settings
            </button>
        </div>
    </form>
</x-filament::page>


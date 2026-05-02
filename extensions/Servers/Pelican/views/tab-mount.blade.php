{{-- Embedded inside Service Show by Pelican::getView(). Mounts a Livewire tab component. --}}
<div class="p-4">
    @livewire($component, ['service' => $service], key('pelican-tab-' . $component . '-' . $service->id))
</div>

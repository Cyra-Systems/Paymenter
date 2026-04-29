@php
    $expiryDays = isset($settings['download_expiry']) ? (int)$settings['download_expiry'] : 0;
    $expiryDate = $expiryDays > 0 ? $service->created_at->addDays($expiryDays) : null;
@endphp

<div class="bg-background-secondary border border-neutral p-6 rounded-lg mt-2">
    <div class="flex flex-col md:flex-row justify-between">
        <h1 class="text-2xl font-semibold">Download Info</h1>
    </div>

    <div class="grid md:grid-cols-2 gap-4 my-4">
        <div class="flex flex-col gap-2">
            <div class="flex items-center text-base">
                <span class="mr-2">Download Count:</span>
                <span class="text-base/50">{{ $service->download_count }} /
                    {{ $settings['download_limit'] > 0 ? $settings['download_limit'] : '∞' }}</span>
            </div>

            @if($expiryDate)
            <div class="flex items-center text-base">
                <span class="mr-2">Expires on:</span>
                <span class="text-base/50">{{ $expiryDate->format('M d, Y H:i:s') }}</span>
            </div>
            <div class="flex items-center text-base">
                <span class="mr-2">Time remaining:</span>
                <span id="download-timer" class="text-red-500 font-semibold"></span>
            </div>
            @endif

            @if(isset($settings['file_checksum']))
            <div x-data="{ showToast: false }" class="mt-2 flex flex-col gap-2">
                <div class="flex flex-row gap-2 flex-wrap items-center">
                    <span class="text-base/50 font-mono break-all flex-1">{{ $settings['file_checksum'] }}</span>
                    <button
                        class="h-fit !w-fit px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                        @click="
                            navigator.clipboard.writeText('{{ $settings['file_checksum'] }}');
                            showToast = true;
                            setTimeout(() => showToast = false, 3000);
                        "
                    >
                        Copy
                    </button>
                </div>

                <div
                    x-show="showToast"
                    x-transition
                    class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50"
                >
                    Checksum copied!
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@if($expiryDate)
<script>
const expiry = new Date("{{ $expiryDate->toIsoString() }}").getTime();
const timerEl = document.querySelector('#download-timer');

setInterval(() => {
    const now = new Date().getTime();
    const distance = expiry - now;

    if (distance < 0) {
        timerEl.innerText = 'Expired';
        return;
    }

    const d = Math.floor(distance / (1000 * 60 * 60 * 24));
    const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const s = Math.floor((distance % (1000 * 60)) / 1000);

    timerEl.innerText = `${d}d ${h}h ${m}m ${s}s`;
}, 1000);
</script>
@endif

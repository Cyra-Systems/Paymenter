<x-filament::page>
    <div class="flex justify-between items-center mb-4">
        <h1>Download Logs</h1>
        <x-filament::button 
            wire:click="clearLogs"
            wire:confirm="Are you sure you want to clear all download logs? This action cannot be undone."
            color="danger">
            Clear All Logs
        </x-filament::button>
    </div>

    <table class="w-full text-left border">
        <thead>
            <tr>
                <th class="text-center">ID</th>
                <th class="text-center">User</th>
                <th class="text-center">Service</th>
                <th class="text-center">Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td class="text-center">{{ $log->id }}</td>
                    <td class="text-center">{{ $log->user?->name ?? 'Unknown' }}</td>
                    <td class="text-center">
                        @if($log->service)
                            <a href="/admin/services/services/{{ $log->service->id }}" class="text-blue-600 hover:underline">
                                {{ $log->service->name ?? 'Service #' . $log->service->id }}
                            </a>
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <footer class="mt-8 text-center text-gray-600">
        <p>DownloadableProducts - QKing and Melvin - {{ date('Y') == 2025 ? '2025' : '2025-' . date('Y') }}</p>
    </footer>
</x-filament::page>
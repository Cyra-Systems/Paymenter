<x-filament-panels::page>
    <div class="prose dark:prose-invert max-w-none mb-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Backups include the database, <code>extensions/</code>, <code>themes/</code>, <code>storage/app/public</code>, and <code>.env</code>.
            They are stored on the local disk under <code>storage/app/{{ config('backup.backup.name', config('app.name', 'Paymenter')) }}/</code>.
            Configure scheduled backups under Settings → Backups.
        </p>
    </div>

    {{ $this->table }}
</x-filament-panels::page>

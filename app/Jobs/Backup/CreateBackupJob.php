<?php

namespace App\Jobs\Backup;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function handle(): void
    {
        try {
            Artisan::call('backup:run', ['--only-to-disk' => 'local']);
            Log::info('Backup created.', ['output' => Artisan::output()]);
        } catch (\Throwable $e) {
            Log::error('Backup failed: ' . $e->getMessage());
            throw $e;
        }
    }
}

<?php

namespace App\Jobs\Backup;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class RestoreDatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public string $backupRelativePath)
    {
    }

    public function handle(): void
    {
        $disk = Storage::disk('local');
        if (!$disk->exists($this->backupRelativePath)) {
            throw new \Exception('Backup file not found: ' . $this->backupRelativePath);
        }

        $tempDir = storage_path('app/backup-restore-' . uniqid());
        if (!mkdir($tempDir, 0755, true)) {
            throw new \Exception('Failed to create restore working directory.');
        }

        try {
            $zipPath = $disk->path($this->backupRelativePath);

            $zip = new \ZipArchive;
            $opened = $zip->open($zipPath);
            if ($opened !== true) {
                throw new \Exception('Failed to open backup archive (code ' . $opened . ').');
            }

            // If the backup is encrypted, set the password before extraction.
            $password = config('backup.backup.encryption');
            if ($password === 'default') {
                $zip->setPassword(config('app.key'));
            }
            $zip->extractTo($tempDir);
            $zip->close();

            $dumpFiles = glob($tempDir . '/db-dumps/*.sql');
            if (empty($dumpFiles)) {
                throw new \Exception('No database dump found inside the backup archive.');
            }

            $this->restoreMysql($dumpFiles[0]);

            Log::info('Database restored from backup', ['file' => $this->backupRelativePath]);
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    private function restoreMysql(string $sqlPath): void
    {
        $db = config('database.connections.' . config('database.default'));
        if (($db['driver'] ?? null) !== 'mysql') {
            throw new \Exception('Database restore is only supported for MySQL connections in v1.');
        }

        $cmd = [
            'mysql',
            '--host=' . $db['host'],
            '--port=' . $db['port'],
            '--user=' . $db['username'],
            '--password=' . $db['password'],
            $db['database'],
        ];

        $process = new Process($cmd);
        $process->setTimeout($this->timeout);
        $process->setInput(file_get_contents($sqlPath));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('mysql restore failed: ' . $process->getErrorOutput());
        }
    }
}

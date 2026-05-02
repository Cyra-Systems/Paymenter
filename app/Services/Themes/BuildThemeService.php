<?php

namespace App\Services\Themes;

use App\Models\Theme;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BuildThemeService
{
    public function build(Theme $theme): array
    {
        $node = $this->resolveNodeBinary();
        if (!$node) {
            return [
                'ok' => false,
                'stdout' => '',
                'stderr' => 'Node.js was not found on this server. Install Node.js and ensure it is on PATH, or set theme_build_node_path via the Settings table to a node binary.',
                'duration_ms' => 0,
            ];
        }

        $themeDir = base_path('themes/' . $theme->name);
        if (!is_dir($themeDir)) {
            return [
                'ok' => false,
                'stdout' => '',
                'stderr' => "Theme directory not found at themes/{$theme->name}.",
                'duration_ms' => 0,
            ];
        }

        $timeoutSeconds = (int) (config('settings.theme_build_timeout', 300));
        $process = new Process(
            command: [$node, 'vite.js', $theme->name],
            cwd: base_path(),
            env: null,
            input: null,
            timeout: $timeoutSeconds,
        );

        $startedAt = microtime(true);
        try {
            $process->run();
        } catch (ProcessFailedException $e) {
            // Process started but failed — captured below.
        }
        $duration = (int) round((microtime(true) - $startedAt) * 1000);

        $stdout = $process->getOutput();
        $stderr = $process->getErrorOutput();
        $ok = $process->isSuccessful();

        $logDir = storage_path('logs');
        if (!is_dir($logDir)) {
            File::makeDirectory($logDir, 0755, true);
        }
        $logPath = $logDir . '/theme-build-' . $theme->name . '-' . date('Ymd-His') . '.log';
        file_put_contents($logPath, "$ {$node} vite.js {$theme->name}\n\n--- STDOUT ---\n{$stdout}\n\n--- STDERR ---\n{$stderr}\n");

        $theme->update([
            'last_built_at' => now(),
            'last_build_status' => $ok ? 'ok' : 'failed',
            'last_build_log_path' => $logPath,
        ]);

        return [
            'ok' => $ok,
            'stdout' => $stdout,
            'stderr' => $stderr,
            'duration_ms' => $duration,
            'log_path' => $logPath,
        ];
    }

    private function resolveNodeBinary(): ?string
    {
        $configured = config('settings.theme_build_node_path');
        if ($configured && is_executable($configured)) {
            return $configured;
        }

        $process = Process::fromShellCommandline('command -v node || which node');
        $process->run();
        $path = trim($process->getOutput());

        return $path !== '' && is_executable($path) ? $path : null;
    }
}

<?php

namespace App\Services\Themes;

use App\Models\Theme;
use Illuminate\Support\Facades\File;

class UploadThemeService
{
    /**
     * Handle an uploaded theme ZIP. Validates structure, extracts to themes/<Name>,
     * inserts a row into the themes table.
     */
    public function handle(string $filePath, ?string $expectedSha256 = null, ?string $expectedSignature = null, ?string $sourceUrl = null): Theme
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \Exception('File does not exist or is not readable.');
        }
        if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'zip') {
            throw new \Exception('Invalid file type. Only zip files are allowed.');
        }

        $actualSha256 = hash_file('sha256', $filePath);
        if ($expectedSha256 !== null && !hash_equals(strtolower($expectedSha256), $actualSha256)) {
            File::delete($filePath);
            throw new \Exception('Checksum mismatch. Expected ' . $expectedSha256 . ', got ' . $actualSha256 . '.');
        }

        if ($expectedSignature !== null) {
            $key = config('settings.marketplace_signing_key');
            if (empty($key)) {
                File::delete($filePath);
                throw new \Exception('A signature was provided but no marketplace_signing_key is configured.');
            }
            if (!hash_equals($expectedSignature, hash_hmac('sha256', $actualSha256, $key))) {
                File::delete($filePath);
                throw new \Exception('Signature verification failed.');
            }
        }

        $extractPath = storage_path('app/themes-staging/' . uniqid());
        if (!is_dir($extractPath) && !mkdir($extractPath, 0755, true)) {
            throw new \Exception('Failed to create staging directory.');
        }

        $this->unzip($filePath, $extractPath);

        try {
            $sourcePath = $this->validateThemePath($extractPath);
            $themeName = $this->resolveThemeName($sourcePath);

            $destinationPath = base_path('themes/' . $themeName);
            $existing = Theme::where('name', $themeName)->first();

            if (is_dir($destinationPath)) {
                File::deleteDirectory($destinationPath);
            }

            if (!rename($sourcePath, $destinationPath)) {
                throw new \Exception('Failed to move theme files into themes/' . $themeName . '.');
            }
        } catch (\Throwable $e) {
            File::deleteDirectory($extractPath);
            throw $e;
        }

        File::deleteDirectory($extractPath);

        $themeMeta = $this->readThemeMeta($destinationPath);

        $attributes = [
            'name' => $themeName,
            'version' => $themeMeta['version'] ?? null,
            'author' => $themeMeta['author'] ?? null,
            'sha256' => $actualSha256,
            'signature' => $expectedSignature,
            'source_url' => $sourceUrl,
            'installed_version' => $themeMeta['version'] ?? null,
        ];

        if ($existing ?? false) {
            $existing->update($attributes);

            return $existing->refresh();
        }

        return Theme::create($attributes);
    }

    private function unzip(string $filePath, string $extractPath): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Failed to open the zip file.');
        }
        $zip->extractTo($extractPath);
        $zip->close();
        File::delete($filePath);
    }

    private function validateThemePath(string $path, int $depth = 0): string
    {
        if ($depth > 1) {
            throw new \Exception('Maximum depth reached while validating theme path.');
        }

        if (file_exists($path . '/theme.php') && file_exists($path . '/vite.config.js')) {
            return $path;
        }

        $subDirs = glob($path . '/*', GLOB_ONLYDIR);
        foreach ($subDirs as $sub) {
            if (basename($sub) === '__MACOSX') {
                continue;
            }
            if (file_exists($sub . '/theme.php') && file_exists($sub . '/vite.config.js')) {
                return $sub;
            }
        }

        if ($depth === 0 && count($subDirs) === 1 && basename($subDirs[0]) !== '__MACOSX') {
            return $this->validateThemePath($subDirs[0], $depth + 1);
        }

        throw new \Exception('Theme is missing required files (theme.php and vite.config.js).');
    }

    private function resolveThemeName(string $path): string
    {
        $meta = $this->readThemeMeta($path);
        $name = $meta['name'] ?? basename($path);

        // Strict slug to avoid path traversal — theme names become directory names.
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '', $name);
        if ($slug === '' || $slug === '.' || $slug === '..') {
            throw new \Exception('Theme name is invalid.');
        }

        return $slug;
    }

    private function readThemeMeta(string $path): array
    {
        $themeFile = $path . '/theme.php';
        if (!file_exists($themeFile)) {
            return [];
        }

        $meta = require $themeFile;

        return is_array($meta) ? $meta : [];
    }
}

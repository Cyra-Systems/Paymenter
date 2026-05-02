<?php

namespace App\Jobs\Marketplace;

use App\Models\MarketplaceListing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncMarketplaceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 1;

    public function handle(): void
    {
        $url = config('settings.marketplace_url');

        if (!$url) {
            Log::warning('Marketplace sync skipped: marketplace_url is not configured.');

            return;
        }

        try {
            $response = Http::timeout(15)
                ->withUserAgent('Paymenter/' . config('app.version') . ' (marketplace-sync)')
                ->get($url);
        } catch (ConnectionException $e) {
            Log::error('Marketplace sync failed: ' . $e->getMessage());

            return;
        }

        if (!$response->successful()) {
            Log::error('Marketplace sync failed', ['status' => $response->status(), 'url' => $url]);

            return;
        }

        $entries = $response->json('extensions', $response->json() ?? []);

        if (!is_array($entries)) {
            Log::error('Marketplace sync failed: response is not an array of entries.');

            return;
        }

        $syncedAt = now();
        $kept = [];

        foreach ($entries as $entry) {
            if (!$this->isValid($entry)) {
                continue;
            }

            $listing = MarketplaceListing::updateOrCreate(
                [
                    'name' => $entry['name'],
                    'type' => $entry['type'],
                    'version' => $entry['meta']['version'] ?? null,
                ],
                [
                    'author' => $entry['meta']['author'] ?? null,
                    'description' => $entry['meta']['description'] ?? null,
                    'icon' => $entry['meta']['icon'] ?? null,
                    'download_url' => $entry['download_url'],
                    'sha256' => $entry['sha256'],
                    'signature' => $entry['signature'] ?? null,
                    'has_migrations' => (bool) ($entry['has_migrations'] ?? false),
                    'synced_at' => $syncedAt,
                    'raw_meta' => $entry,
                ]
            );

            $kept[] = $listing->id;
        }

        // Drop listings that no longer appear in the manifest.
        MarketplaceListing::whereNotIn('id', $kept)->delete();

        Log::info('Marketplace sync complete', ['count' => count($kept)]);
    }

    private function isValid(array $entry): bool
    {
        if (empty($entry['name']) || empty($entry['type']) || empty($entry['download_url']) || empty($entry['sha256'])) {
            return false;
        }

        if (!in_array($entry['type'], ['gateway', 'server', 'other', 'theme'], true)) {
            return false;
        }

        if (!preg_match('/^[a-f0-9]{64}$/i', $entry['sha256'])) {
            return false;
        }

        return true;
    }
}

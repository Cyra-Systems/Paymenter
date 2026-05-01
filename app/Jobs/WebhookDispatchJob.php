<?php

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 120, 300];

    public function __construct(
        public readonly int $webhookId,
        public readonly string $event,
        public readonly array $payload
    ) {}

    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);

        if (!$webhook || !$webhook->enabled) {
            return;
        }

        $body = json_encode([
            'event'     => $this->event,
            'timestamp' => now()->toIso8601String(),
            'data'      => $this->payload,
        ], JSON_UNESCAPED_SLASHES);

        $signature = 'sha256=' . hash_hmac('sha256', $body, $webhook->secret);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type'        => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event'     => $this->event,
                    'X-Webhook-Delivery'  => $this->job?->uuid() ?? (string) Str::uuid(),
                ])
                ->send('POST', $webhook->url, ['body' => $body]);
        } catch (\Exception $e) {
            // Network-level failure (timeout, DNS, TLS, etc.) — store 0 so the UI
            // shows a red indicator and re-throw so the job queue retries.
            $webhook->updateQuietly(['last_response_status' => 0]);
            Log::warning("Webhook #{$webhook->id} ({$this->event}) network error: " . $e->getMessage());
            throw $e;
        }

        if (!$response->successful()) {
            $webhook->updateQuietly(['last_response_status' => $response->status()]);
            Log::warning("Webhook #{$webhook->id} ({$this->event}) returned HTTP {$response->status()}");
            throw new \RuntimeException(
                "Webhook #{$webhook->id} delivery failed: HTTP {$response->status()}"
            );
        }

        $webhook->updateQuietly([
            'last_called_at'       => now(),
            'last_response_status' => $response->status(),
        ]);
    }
}

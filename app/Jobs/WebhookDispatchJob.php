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

        $response = Http::timeout(10)
            ->withHeaders([
                'Content-Type'        => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event'     => $this->event,
                'X-Webhook-Delivery'  => $this->job?->uuid() ?? (string) \Illuminate\Support\Str::uuid(),
            ])
            ->send('POST', $webhook->url, ['body' => $body]);

        if (!$response->successful()) {
            Log::warning("Webhook #{$webhook->id} ({$this->event}) returned HTTP {$response->status()}");
            throw new \RuntimeException(
                "Webhook #{$webhook->id} delivery failed: HTTP {$response->status()}"
            );
        }

        $webhook->updateQuietly(['last_called_at' => now()]);
    }
}

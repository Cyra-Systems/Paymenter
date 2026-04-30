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

    public int $backoff = 60;

    public function __construct(
        public readonly int $userId,
        public readonly string $event,
        public readonly array $payload
    ) {}

    public function handle(): void
    {
        $webhooks = Webhook::where('user_id', $this->userId)
            ->where('enabled', true)
            ->get()
            ->filter(fn ($webhook) => in_array($this->event, $webhook->events ?? []));

        if ($webhooks->isEmpty()) {
            return;
        }

        $body = json_encode([
            'event'     => $this->event,
            'timestamp' => now()->toIso8601String(),
            'data'      => $this->payload,
        ], JSON_UNESCAPED_SLASHES);

        foreach ($webhooks as $webhook) {
            $signature = 'sha256=' . hash_hmac('sha256', $body, $webhook->secret);

            try {
                Http::timeout(10)
                    ->withHeaders([
                        'Content-Type'        => 'application/json',
                        'X-Webhook-Signature' => $signature,
                        'X-Webhook-Event'     => $this->event,
                    ])
                    ->send('POST', $webhook->url, ['body' => $body]);

                $webhook->last_called_at = now();
                $webhook->save();
            } catch (\Exception $e) {
                Log::warning("Webhook delivery failed for webhook #{$webhook->id} ({$this->event}): " . $e->getMessage());
                // Re-throw so the job queue retries
                throw $e;
            }
        }
    }
}

<?php

namespace App\Listeners;

use App\Events\Invoice\Finalized as InvoiceFinalized;
use App\Events\Invoice\Paid as InvoicePaid;
use App\Events\Order\Finalized as OrderFinalized;
use App\Events\Service\Updated as ServiceUpdated;
use App\Jobs\WebhookDispatchJob;
use App\Models\Service;
use App\Models\Webhook;

class WebhookListener
{
    /**
     * Handle webhook-triggering events.
     *
     * Invoice\Finalized and Order\Finalized are dispatched afterResponse() in
     * their observers, so all DB writes are committed before any job is queued.
     */
    public function handle(InvoicePaid|InvoiceFinalized|OrderFinalized|ServiceUpdated $event): void
    {
        if ($event instanceof InvoicePaid) {
            $this->dispatchWebhooks($event->invoice->user_id, 'invoice.paid', [
                'invoice_id'     => $event->invoice->id,
                'invoice_number' => $event->invoice->number,
                'total'          => (string) $event->invoice->total,
                'currency_code'  => $event->invoice->currency_code,
                'status'         => $event->invoice->status,
                'paid_at'        => now()->toIso8601String(),
            ]);
            return;
        }

        if ($event instanceof InvoiceFinalized) {
            $invoice = $event->invoice;
            $invoice->loadMissing('items');

            if ($invoice->total <= 0) {
                return;
            }

            $this->dispatchWebhooks($invoice->user_id, 'invoice.created', [
                'invoice_id'     => $invoice->id,
                'invoice_number' => $invoice->number,
                'total'          => (string) $invoice->total,
                'currency_code'  => $invoice->currency_code,
                'status'         => $invoice->status,
                'due_at'         => $invoice->due_at?->toIso8601String(),
                'created_at'     => $invoice->created_at?->toIso8601String(),
            ]);
            return;
        }

        if ($event instanceof OrderFinalized) {
            $order = $event->order;
            $order->loadMissing('services');

            $this->dispatchWebhooks($order->user_id, 'order.created', [
                'order_id'      => $order->id,
                'currency_code' => $order->currency_code,
                'service_ids'   => $order->services->pluck('id')->all(),
                'created_at'    => $order->created_at?->toIso8601String(),
            ]);
            return;
        }

        if ($event instanceof ServiceUpdated) {
            $service = $event->service;

            if ($service->status !== Service::STATUS_ACTIVE || !$service->wasChanged('status')) {
                return;
            }

            $this->dispatchWebhooks($service->user_id, 'service.active', [
                'service_id' => $service->id,
                'product_id' => $service->product_id,
                'plan_id'    => $service->plan_id,
                'status'     => $service->status,
                'expires_at' => $service->expires_at?->toIso8601String(),
            ]);
        }
    }

    private function dispatchWebhooks(int $userId, string $event, array $payload): void
    {
        Webhook::where('user_id', $userId)
            ->where('enabled', true)
            ->get()
            ->filter(fn ($webhook) => in_array($event, $webhook->events ?? []))
            ->each(fn ($webhook) => WebhookDispatchJob::dispatch($webhook->id, $event, $payload));
    }
}

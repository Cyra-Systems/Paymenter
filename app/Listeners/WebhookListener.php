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
     * Fires after the HTTP response (Invoice\Finalized / Order\Finalized are
     * dispatched afterResponse() in their respective observers), so all DB
     * writes are committed before any webhook job is queued.
     */
    public function handle(InvoicePaid|InvoiceFinalized|OrderFinalized|ServiceUpdated $event): void
    {
        if ($event instanceof InvoicePaid) {
            $this->handleInvoicePaid($event->invoice);
            return;
        }

        if ($event instanceof InvoiceFinalized) {
            $this->handleInvoiceCreated($event->invoice);
            return;
        }

        if ($event instanceof OrderFinalized) {
            $this->handleOrderCreated($event->order);
            return;
        }

        if ($event instanceof ServiceUpdated) {
            $this->handleServiceUpdated($event->service);
        }
    }

    private function handleInvoicePaid($invoice): void
    {
        if (!$this->userHasWebhooks($invoice->user_id)) {
            return;
        }

        WebhookDispatchJob::dispatch($invoice->user_id, 'invoice.paid', [
            'invoice_id'     => $invoice->id,
            'invoice_number' => $invoice->number,
            'total'          => (string) $invoice->total,
            'currency_code'  => $invoice->currency_code,
            'status'         => $invoice->status,
            'paid_at'        => now()->toIso8601String(),
        ]);
    }

    private function handleInvoiceCreated($invoice): void
    {
        // Reload with items so ->total resolves correctly and skip free invoices
        $invoice->loadMissing('items');

        if ($invoice->total <= 0) {
            return;
        }

        if (!$this->userHasWebhooks($invoice->user_id)) {
            return;
        }

        WebhookDispatchJob::dispatch($invoice->user_id, 'invoice.created', [
            'invoice_id'     => $invoice->id,
            'invoice_number' => $invoice->number,
            'total'          => (string) $invoice->total,
            'currency_code'  => $invoice->currency_code,
            'status'         => $invoice->status,
            'due_at'         => $invoice->due_at?->toIso8601String(),
            'created_at'     => $invoice->created_at?->toIso8601String(),
        ]);
    }

    private function handleOrderCreated($order): void
    {
        if (!$this->userHasWebhooks($order->user_id)) {
            return;
        }

        $order->loadMissing('services');

        WebhookDispatchJob::dispatch($order->user_id, 'order.created', [
            'order_id'      => $order->id,
            'currency_code' => $order->currency_code,
            'service_ids'   => $order->services->pluck('id')->all(),
            'created_at'    => $order->created_at?->toIso8601String(),
        ]);
    }

    private function handleServiceUpdated($service): void
    {
        if ($service->status !== Service::STATUS_ACTIVE || !$service->wasChanged('status')) {
            return;
        }

        if (!$this->userHasWebhooks($service->user_id)) {
            return;
        }

        WebhookDispatchJob::dispatch($service->user_id, 'service.active', [
            'service_id' => $service->id,
            'product_id' => $service->product_id,
            'plan_id'    => $service->plan_id,
            'status'     => $service->status,
            'expires_at' => $service->expires_at?->toIso8601String(),
        ]);
    }

    private function userHasWebhooks(int $userId): bool
    {
        return Webhook::where('user_id', $userId)->where('enabled', true)->exists();
    }
}

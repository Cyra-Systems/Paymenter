<?php

namespace App\Listeners;

use App\Events\Invoice\Paid as InvoicePaid;
use App\Events\Service\Updated as ServiceUpdated;
use App\Jobs\WebhookDispatchJob;
use App\Models\Service;
use App\Models\Webhook;

class WebhookListener
{
    /**
     * Fire the invoice.paid webhook when an invoice is marked as paid.
     * Fire the service.active webhook when a service transitions to active status.
     *
     * Auto-discovered by Laravel via the union-type handle() signature.
     */
    public function handle(InvoicePaid|ServiceUpdated $event): void
    {
        if ($event instanceof InvoicePaid) {
            $invoice = $event->invoice;

            if (!Webhook::where('user_id', $invoice->user_id)->where('enabled', true)->exists()) {
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

            return;
        }

        if ($event instanceof ServiceUpdated) {
            $service = $event->service;

            if ($service->status !== Service::STATUS_ACTIVE || !$service->wasChanged('status')) {
                return;
            }

            if (!Webhook::where('user_id', $service->user_id)->where('enabled', true)->exists()) {
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
    }
}

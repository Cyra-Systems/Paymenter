<?php

namespace App\Http\Controllers\Api\User;

use App\Helpers\ExtensionHelper;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class InvoiceController extends UserApiController
{
    protected const INCLUDES = ['items', 'items.reference'];

    public function index(Request $request)
    {
        $this->checkPermission('invoices.view');

        $perPage = min((int) $request->input('per_page', 15), 100);

        $invoices = QueryBuilder::for(Invoice::class)
            ->where('user_id', $this->apiUser()->id)
            ->allowedFilters(['status', 'currency_code'])
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->allowedSorts(['id', 'status', 'due_at', 'created_at'])
            ->simplePaginate($perPage);

        return InvoiceResource::collection($invoices);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->checkPermission('invoices.view');

        if ($invoice->user_id !== $this->apiUser()->id) {
            return $this->apiError('RESOURCE_NOT_OWNED', 'This invoice does not belong to your account.', 403);
        }

        $invoice = QueryBuilder::for(Invoice::class)
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->findOrFail($invoice->id);

        return new InvoiceResource($invoice);
    }

    public function pay(Request $request, Invoice $invoice)
    {
        $this->checkPermission('invoices.pay');

        if ($invoice->user_id !== $this->apiUser()->id) {
            return $this->apiError('RESOURCE_NOT_OWNED', 'This invoice does not belong to your account.', 403);
        }

        if ($invoice->status !== Invoice::STATUS_PENDING) {
            return $this->apiError(
                'INVOICE_NOT_PAYABLE',
                'This invoice cannot be paid (status: ' . $invoice->status . ').',
                422
            );
        }

        $request->validate([
            'payment_method' => 'required|string|in:credits',
        ]);

        DB::beginTransaction();
        try {
            $invoice->load('items', 'transactions');
            $remaining = $invoice->remaining;

            if ($remaining <= 0) {
                DB::rollBack();
                return $this->apiError('INVOICE_NOT_PAYABLE', 'This invoice has no outstanding balance.', 422);
            }

            $credit = $this->apiUser()
                ->credits()
                ->where('currency_code', $invoice->currency_code)
                ->lockForUpdate()
                ->first();

            if (!$credit || $credit->amount <= 0) {
                DB::rollBack();
                return $this->apiError(
                    'INSUFFICIENT_CREDITS',
                    'You do not have sufficient credits in ' . $invoice->currency_code . ' to pay this invoice.',
                    422
                );
            }

            $amountPaid = min((float) $credit->amount, (float) $remaining);

            $credit->amount = (float) $credit->amount - $amountPaid;
            $credit->save();

            ExtensionHelper::addPayment(
                $invoice->id,
                null,
                amount: $amountPaid,
                isCreditTransaction: true
            );

            DB::commit();

            $invoice->refresh();

            $newRemaining = $invoice->remaining;

            return response()->json([
                'data' => [
                    'invoice_id'   => $invoice->id,
                    'status'       => $invoice->status,
                    'amount_paid'  => number_format($amountPaid, 2, '.', ''),
                    'remaining'    => number_format(max($newRemaining, 0), 2, '.', ''),
                    'fully_paid'   => $invoice->status === Invoice::STATUS_PAID,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return $this->apiError('SERVER_ERROR', 'An error occurred while processing the payment. Please try again.', 500);
        }
    }
}

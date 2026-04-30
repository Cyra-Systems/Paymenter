<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class InvoiceController extends UserApiController
{
    protected const INCLUDES = ['items', 'items.reference'];

    public function index(Request $request)
    {
        $this->checkPermission('invoices.view');

        $invoices = QueryBuilder::for(Invoice::class)
            ->where('user_id', $this->apiUser()->id)
            ->allowedFilters(['status', 'currency_code'])
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->allowedSorts(['id', 'status', 'due_at', 'created_at'])
            ->simplePaginate(request('per_page', 15));

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
}

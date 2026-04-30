<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\ServiceCancellation;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class ServiceController extends UserApiController
{
    protected const INCLUDES = ['order', 'product', 'invoices', 'properties'];

    public function index(Request $request)
    {
        $this->checkPermission('services.view');

        $services = QueryBuilder::for(Service::class)
            ->where('user_id', $this->apiUser()->id)
            ->allowedFilters(['status', 'product_id'])
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->allowedSorts(['id', 'status', 'expires_at', 'created_at'])
            ->simplePaginate(request('per_page', 15));

        return ServiceResource::collection($services);
    }

    public function show(Request $request, Service $service)
    {
        $this->checkPermission('services.view');

        if ($service->user_id !== $this->apiUser()->id) {
            return $this->apiError('RESOURCE_NOT_OWNED', 'This service does not belong to your account.', 403);
        }

        $service = QueryBuilder::for(Service::class)
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->findOrFail($service->id);

        return new ServiceResource($service);
    }

    public function destroy(Request $request, Service $service)
    {
        $this->checkPermission('services.cancel');

        if ($service->user_id !== $this->apiUser()->id) {
            return $this->apiError('RESOURCE_NOT_OWNED', 'This service does not belong to your account.', 403);
        }

        if ($service->status === Service::STATUS_CANCELLED) {
            return $this->apiError('SERVICE_ALREADY_CANCELLED', 'This service has already been cancelled.', 422);
        }

        ServiceCancellation::create([
            'service_id' => $service->id,
            'reason' => $request->input('reason', 'Cancelled via user API'),
            'type' => 'immediate',
        ]);

        $service->status = Service::STATUS_CANCELLED;
        $service->save();

        return $this->returnNoContent();
    }
}

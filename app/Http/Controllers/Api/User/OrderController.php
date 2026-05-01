<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Resources\OrderResource;
use App\Jobs\Server\CreateJob;
use App\Models\ConfigOption;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class OrderController extends UserApiController
{
    protected const INCLUDES = ['services', 'services.product', 'services.invoices'];

    public function index(Request $request)
    {
        $this->checkPermission('orders.view');

        $perPage = min((int) $request->input('per_page', 15), 100);

        $orders = QueryBuilder::for(Order::class)
            ->where('user_id', $this->apiUser()->id)
            ->allowedFilters(['currency_code'])
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->allowedSorts(['id', 'created_at'])
            ->simplePaginate($perPage);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        $this->checkPermission('orders.view');

        if ($order->user_id !== $this->apiUser()->id) {
            return $this->apiError('RESOURCE_NOT_OWNED', 'This order does not belong to your account.', 403);
        }

        $order = QueryBuilder::for(Order::class)
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->findOrFail($order->id);

        return new OrderResource($order);
    }

    public function store(Request $request)
    {
        $this->checkPermission('orders.create');

        $validated = $request->validate([
            'currency_code'                          => 'required|string|size:3|exists:currencies,code',
            'items'                                  => 'required|array|min:1|max:15',
            'items.*.product_id'                     => 'required|integer|exists:products,id',
            'items.*.plan_id'                        => 'required|integer|exists:plans,id',
            'items.*.quantity'                       => 'sometimes|integer|min:1|max:100',
            'items.*.config_options'                 => 'sometimes|array',
            'items.*.config_options.*.option_id'     => 'required_with:items.*.config_options|integer|exists:config_options,id',
            'items.*.config_options.*.value'         => 'nullable',
            'items.*.checkout_config'                => 'sometimes|array|max:50',
        ]);

        $currencyCode = $validated['currency_code'];
        $items        = $validated['items'];

        DB::beginTransaction();
        try {
            $user = User::where('id', $this->apiUser()->id)->lockForUpdate()->first();

            // Validate each item and resolve models
            $resolvedItems = [];
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if (!$product->user_api) {
                    DB::rollBack();
                    return $this->apiError(
                        'PRODUCT_NOT_API_ACCESSIBLE',
                        "Product [{$product->name}] is not accessible via the user API.",
                        422
                    );
                }

                $plan = Plan::where('id', $item['plan_id'])
                    ->where('priceable_type', Product::class)
                    ->where('priceable_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if (!$plan) {
                    DB::rollBack();
                    return $this->apiError(
                        'PLAN_PRODUCT_MISMATCH',
                        "The specified plan does not belong to product [{$product->name}].",
                        422
                    );
                }

                $quantity = $item['quantity'] ?? 1;

                if ($product->stock !== null && $product->stock < $quantity) {
                    DB::rollBack();
                    return $this->apiError(
                        'INSUFFICIENT_STOCK',
                        "Product [{$product->name}] does not have sufficient stock.",
                        422
                    );
                }

                if ($product->per_user_limit > 0) {
                    $existing = Service::where('user_id', $user->id)
                        ->where('product_id', $product->id)
                        ->whereNotIn('status', [Service::STATUS_CANCELLED])
                        ->lockForUpdate()
                        ->count();

                    if ($existing + $quantity > $product->per_user_limit) {
                        DB::rollBack();
                        return $this->apiError(
                            'USER_LIMIT_EXCEEDED',
                            "Product [{$product->name}] exceeds your per-user limit.",
                            422
                        );
                    }
                }

                // Validate checkout_config keys (S-03: prevent arbitrary property injection)
                foreach ($item['checkout_config'] ?? [] as $key => $value) {
                    if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', (string) $key)) {
                        DB::rollBack();
                        return $this->apiError(
                            'VALIDATION_ERROR',
                            "Invalid checkout_config key [{$key}]: keys must be alphanumeric with underscores, max 64 characters.",
                            400
                        );
                    }
                }

                // Resolve unit price for the requested currency (lock the plan's prices)
                $plan->load('prices');
                $priceModel = $plan->prices->where('currency_code', $currencyCode)->first();
                $unitPrice  = $priceModel ? (float) $priceModel->price : 0.0;

                // Resolve config options server-side (S-04: never trust client-supplied key names)
                $resolvedConfigOptions = [];
                foreach ($item['config_options'] ?? [] as $configOptionInput) {
                    $optionModel = ConfigOption::find($configOptionInput['option_id']);
                    if (!$optionModel) {
                        continue;
                    }
                    $resolvedConfigOptions[] = [
                        'model' => $optionModel,
                        'value' => $configOptionInput['value'] ?? null,
                    ];
                }

                $resolvedItems[] = [
                    'product'        => $product,
                    'plan'           => $plan,
                    'quantity'       => $quantity,
                    'unit_price'     => $unitPrice,
                    'checkout_config'=> $item['checkout_config'] ?? [],
                    'config_options' => $resolvedConfigOptions,
                ];
            }

            // Decrement stock atomically after all checks pass
            foreach ($resolvedItems as $ri) {
                if ($ri['product']->stock !== null) {
                    $ri['product']->stock -= $ri['quantity'];
                    $ri['product']->save();
                }
            }

            // Compute order total
            $orderTotal = (float) array_sum(
                array_map(fn ($ri) => $ri['unit_price'] * $ri['quantity'], $resolvedItems)
            );

            // Create order
            $order = new Order([
                'user_id'       => $user->id,
                'currency_code' => $currencyCode,
            ]);
            $order->save();

            // Create invoice only when the total is non-zero
            $invoice = null;
            if ($orderTotal > 0) {
                $invoice = new Invoice([
                    'user_id'       => $user->id,
                    'due_at'        => now()->addDays(7),
                    'currency_code' => $currencyCode,
                ]);
                $invoice->save();
            }

            // Create services and attach invoice items
            foreach ($resolvedItems as $ri) {
                $service = $order->services()->create([
                    'user_id'       => $user->id,
                    'currency_code' => $currencyCode,
                    'product_id'    => $ri['product']->id,
                    'plan_id'       => $ri['plan']->id,
                    'price'         => $ri['unit_price'],
                    'quantity'      => $ri['quantity'],
                    'status'        => Service::STATUS_PENDING,
                ]);

                // Persist validated checkout config (S-03: keys already validated above)
                foreach ($ri['checkout_config'] as $key => $value) {
                    $safeValue = is_string($value) ? substr($value, 0, 1000) : $value;
                    $service->properties()->updateOrCreate(['key' => $key], ['value' => $safeValue]);
                }

                // Persist config option selections using server-resolved field names (S-04)
                foreach ($ri['config_options'] as $co) {
                    /** @var ConfigOption $optionModel */
                    $optionModel = $co['model'];
                    $value       = $co['value'];

                    if (in_array($optionModel->type, ['text', 'number'])) {
                        if ($value === null) {
                            continue;
                        }
                        $service->properties()->updateOrCreate(
                            ['key' => $optionModel->env_variable ?? $optionModel->name],
                            ['name' => $optionModel->name, 'value' => $value]
                        );
                        continue;
                    }

                    if ($value === null) {
                        continue;
                    }

                    $service->configs()->create([
                        'config_option_id' => $optionModel->id,
                        'config_value_id'  => $value,
                    ]);
                }

                $lineTotal = $ri['unit_price'] * $ri['quantity'];

                if ($invoice && $lineTotal > 0) {
                    $invoice->items()->create([
                        'reference_id'   => $service->id,
                        'reference_type' => Service::class,
                        'price'          => $ri['unit_price'],
                        'quantity'       => $ri['quantity'],
                        'description'    => $service->description,
                    ]);
                } else {
                    // Free / zero-price plan — activate immediately
                    if ($service->product->server) {
                        CreateJob::dispatch($service);
                    }
                    $service->status     = Service::STATUS_ACTIVE;
                    $service->expires_at = $service->calculateNextDueDate();
                    $service->save();
                }
            }

            DB::commit();

            $order->load('services');

            return (new OrderResource($order))
                ->additional([
                    'meta' => [
                        'invoice_id'     => $invoice?->id,
                        'invoice_status' => $invoice?->status,
                        'services'       => $order->services->map(fn ($s) => [
                            'id'     => $s->id,
                            'status' => $s->status,
                        ]),
                    ],
                ])
                ->response()
                ->setStatusCode(201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return $this->apiError('SERVER_ERROR', 'An error occurred while processing the order. Please try again later.', 500);
        }
    }
}

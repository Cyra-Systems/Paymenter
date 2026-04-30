<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends UserApiController
{
    protected const INCLUDES = ['category', 'plans.prices'];

    public function index(Request $request)
    {
        $this->checkPermission('catalog.view');

        $products = QueryBuilder::for(Product::class)
            ->where('user_api', true)
            ->allowedFilters(['name', AllowedFilter::exact('category_id')])
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->allowedSorts(['id', 'name', 'sort', 'stock', 'created_at'])
            ->simplePaginate(request('per_page', 15));

        return ProductResource::collection($products);
    }

    public function show(Request $request, Product $product)
    {
        $this->checkPermission('catalog.view');

        if (!$product->user_api) {
            return $this->apiError('PRODUCT_NOT_API_ACCESSIBLE', 'This product is not accessible via the user API.', 404);
        }

        $product = QueryBuilder::for(Product::class)
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->findOrFail($product->id);

        return new ProductResource($product);
    }
}

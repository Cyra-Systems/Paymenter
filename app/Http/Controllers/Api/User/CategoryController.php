<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryController extends UserApiController
{
    protected const INCLUDES = ['products', 'parent', 'children'];

    public function index(Request $request)
    {
        $this->checkPermission('catalog.view');

        $perPage = min((int) $request->input('per_page', 15), 100);

        $categories = QueryBuilder::for(
                Category::whereHas('products', fn ($q) => $q->where('user_api', true))
            )
            ->allowedFilters(['name', 'parent_id'])
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->allowedSorts(['id', 'name'])
            ->simplePaginate($perPage);

        $this->scopeProductsInclude($categories->items());

        return CategoryResource::collection($categories);
    }

    public function show(Request $request, Category $category)
    {
        $this->checkPermission('catalog.view');

        if (!$category->products()->where('user_api', true)->exists()) {
            return $this->apiError('NOT_FOUND', 'Category not found.', 404);
        }

        $category = QueryBuilder::for(Category::class)
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->findOrFail($category->id);

        $this->scopeProductsInclude([$category]);

        return new CategoryResource($category);
    }

    /**
     * If the products relation was eagerly loaded, strip any non-user_api products
     * so they are never serialised into the API response.
     */
    private function scopeProductsInclude(array $categories): void
    {
        foreach ($categories as $category) {
            if ($category->relationLoaded('products')) {
                $category->setRelation(
                    'products',
                    $category->getRelation('products')
                        ->filter(fn ($p) => (bool) $p->user_api)
                        ->values()
                );
            }
        }
    }
}

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

        $categories = QueryBuilder::for(Category::class)
            ->allowedFilters(['name', 'parent_id'])
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->allowedSorts(['id', 'name'])
            ->simplePaginate(request('per_page', 15));

        return CategoryResource::collection($categories);
    }

    public function show(Request $request, Category $category)
    {
        $this->checkPermission('catalog.view');

        $category = QueryBuilder::for(Category::class)
            ->allowedIncludes($this->userAllowedIncludes(self::INCLUDES))
            ->findOrFail($category->id);

        return new CategoryResource($category);
    }
}

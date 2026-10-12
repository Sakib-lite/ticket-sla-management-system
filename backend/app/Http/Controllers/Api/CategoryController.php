<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $includeInactive = $request->boolean('include_inactive') && $request->user()->can('viewInactive', Category::class);

        $categories = $includeInactive
            ? Category::query()->orderBy('name')->get()
            : Category::activeCached();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $name = $request->validated('name');

        return CategoryResource::make(Category::create(['name' => $name, 'slug' => Str::slug($name)]));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $attributes = $request->validated();

        if (isset($attributes['name'])) {
            $attributes['slug'] = Str::slug($attributes['name']);
        }

        $category->update($attributes);

        return CategoryResource::make($category);
    }
}

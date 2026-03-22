<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductIndexRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(ProductIndexRequest $request): JsonResponse
    {
        return $this->ok(
            ProductResource::collection($this->productService->paginate($request->validated())),
            'Products retrieved successfully.',
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create(
            $request->safe()->except(['image']),
            $request->file('image'),
        );

        return $this->created(
            new ProductResource($product),
            'Product created successfully.',
        );
    }

    public function show(Product $product): JsonResponse
    {
        return $this->ok(
            new ProductResource($product),
            'Product retrieved successfully.',
        );
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update(
            $product,
            $request->safe()->except(['image']),
            $request->file('image'),
        );

        return $this->ok(
            new ProductResource($product),
            'Product updated successfully.',
        );
    }

    public function destroy(Product $product): Response
    {
        $this->productService->delete($product);

        return $this->noContent();
    }
}

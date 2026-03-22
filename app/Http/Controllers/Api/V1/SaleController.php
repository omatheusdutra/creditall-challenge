<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaleIndexRequest;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Http\Requests\Sales\UpdateSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SaleController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly SaleService $saleService,
    ) {}

    public function index(SaleIndexRequest $request): JsonResponse
    {
        return $this->ok(
            SaleResource::collection($this->saleService->paginate($request->validated())),
            'Sales retrieved successfully.',
        );
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->create($request->validated());

        return $this->created(
            new SaleResource($sale),
            'Sale created successfully.',
        );
    }

    public function show(Sale $sale): JsonResponse
    {
        return $this->ok(
            new SaleResource($sale->loadMissing([
                'product:id,name',
                'customer:id,name',
            ])),
            'Sale retrieved successfully.',
        );
    }

    public function update(UpdateSaleRequest $request, Sale $sale): JsonResponse
    {
        $sale = $this->saleService->update($sale, $request->validated());

        return $this->ok(
            new SaleResource($sale),
            'Sale updated successfully.',
        );
    }

    public function destroy(Sale $sale): Response
    {
        $this->saleService->delete($sale);

        return $this->noContent();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\CustomerIndexRequest;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function index(CustomerIndexRequest $request): JsonResponse
    {
        return $this->ok(
            CustomerResource::collection($this->customerService->paginate($request->validated())),
            'Customers retrieved successfully.',
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->create($request->validated());

        return $this->created(
            new CustomerResource($customer),
            'Customer created successfully.',
        );
    }

    public function show(Customer $customer): JsonResponse
    {
        return $this->ok(
            new CustomerResource($customer),
            'Customer retrieved successfully.',
        );
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer = $this->customerService->update($customer, $request->validated());

        return $this->ok(
            new CustomerResource($customer),
            'Customer updated successfully.',
        );
    }

    public function destroy(Customer $customer): Response
    {
        $this->customerService->delete($customer);

        return $this->noContent();
    }
}

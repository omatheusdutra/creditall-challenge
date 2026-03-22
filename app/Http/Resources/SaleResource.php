<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\SaleStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof SaleStatus ? $this->status->value : $this->status;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'customer_id' => $this->customer_id,
            'sold_at' => $this->sold_at?->toISOString(),
            'quantity' => $this->quantity,
            'discount' => $this->discount,
            'status' => $status,
            'unit_price' => $this->unit_price,
            'gross_amount' => $this->gross_amount,
            'final_amount' => $this->final_amount,
            'product' => $this->whenLoaded('product', fn (): ProductSummaryResource => new ProductSummaryResource($this->product)),
            'customer' => $this->whenLoaded('customer', fn (): CustomerSummaryResource => new CustomerSummaryResource($this->customer)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

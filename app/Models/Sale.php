<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SaleStatus;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'customer_id',
        'sold_at',
        'quantity',
        'discount',
        'status',
        'unit_price',
        'gross_amount',
        'final_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'quantity' => 'integer',
            'discount' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'status' => SaleStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

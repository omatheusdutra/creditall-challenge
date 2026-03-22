<?php

declare(strict_types=1);

use App\Enums\SaleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->dateTime('sold_at')->index();
            $table->unsignedInteger('quantity');
            $table->decimal('discount', 12, 2)->default('0.00');
            $table->enum('status', SaleStatus::values())->index();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('final_amount', 12, 2);
            $table->timestamps();

            $table->index(['customer_id', 'sold_at']);
            $table->index(['product_id', 'sold_at']);
        });

        DB::statement('ALTER TABLE sales ADD CONSTRAINT chk_sales_discount_lte_gross_amount CHECK (discount <= gross_amount)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT chk_sales_discount_non_negative CHECK (discount >= 0)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT chk_sales_final_amount_consistent CHECK (final_amount = (gross_amount - discount))');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT chk_sales_final_amount_non_negative CHECK (final_amount >= 0)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT chk_sales_gross_amount_non_negative CHECK (gross_amount >= 0)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT chk_sales_quantity_positive CHECK (quantity > 0)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT chk_sales_unit_price_positive CHECK (unit_price > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

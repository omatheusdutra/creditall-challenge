<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150)->index();
            $table->text('description');
            $table->decimal('price', 12, 2)->index();
            $table->string('image_path')->nullable();
            $table->timestamps();

            $table->fullText(['name', 'description'], 'ft_products_search');
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT chk_products_price_positive CHECK (price > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

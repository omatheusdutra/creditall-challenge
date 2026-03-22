<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150)->index();
            $table->string('email', 150)->unique();
            $table->char('cpf', 11)->unique();
            $table->timestamps();

            $table->fullText(['name', 'email'], 'ft_customers_search');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

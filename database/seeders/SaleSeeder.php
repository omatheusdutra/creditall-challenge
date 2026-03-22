<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Sale;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        Sale::factory()->count(15)->create();
    }
}

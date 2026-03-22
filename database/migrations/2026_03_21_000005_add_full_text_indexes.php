<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Full-text indexes are applied in the create migrations to keep fresh installs and tests fast.
    }

    public function down(): void
    {
        // Intentionally blank. Full-text indexes are managed by table recreation in fresh installs.
    }
};

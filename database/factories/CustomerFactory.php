<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'cpf' => $this->generateValidCpf(),
        ];
    }

    private function generateValidCpf(): string
    {
        $digits = [];

        for ($index = 0; $index < 9; $index++) {
            $digits[] = fake()->numberBetween(0, 9);
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;

            foreach ($digits as $index => $digit) {
                $sum += $digit * (($position + 1) - $index);
            }

            $digits[] = ((10 * $sum) % 11) % 10;
        }

        return implode('', $digits);
    }
}

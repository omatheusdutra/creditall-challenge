<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();

        Customer::query()->upsert(
            array_map(fn (array $customer, int $index): array => [
                'name' => $customer['name'],
                'email' => $customer['email'],
                'cpf' => $this->generateValidCpf($index + 1),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $this->customers(), array_keys($this->customers())),
            ['email'],
            ['name', 'cpf', 'updated_at'],
        );
    }

    /**
     * @return array<int, array{name: string, email: string}>
     */
    private function customers(): array
    {
        return [
            ['name' => 'Ana Beatriz Lima', 'email' => 'ana.lima@atlaslog.com.br'],
            ['name' => 'Bruno Carvalho', 'email' => 'bruno.carvalho@nexaoffice.com.br'],
            ['name' => 'Camila Rocha', 'email' => 'camila.rocha@primehealth.com.br'],
            ['name' => 'Daniel Martins', 'email' => 'daniel.martins@aurorafoods.com.br'],
            ['name' => 'Fernanda Souza', 'email' => 'fernanda.souza@vectorretail.com.br'],
            ['name' => 'Gustavo Ribeiro', 'email' => 'gustavo.ribeiro@montrealtech.com.br'],
            ['name' => 'Helena Costa', 'email' => 'helena.costa@bravocapital.com.br'],
            ['name' => 'Igor Almeida', 'email' => 'igor.almeida@orionservices.com.br'],
            ['name' => 'Juliana Torres', 'email' => 'juliana.torres@lumenenergia.com.br'],
            ['name' => 'Marcos Fernandes', 'email' => 'marcos.fernandes@altacare.com.br'],
        ];
    }

    private function generateValidCpf(int $seed): string
    {
        $digits = str_pad((string) (100000000 + ($seed * 7919)), 9, '0', STR_PAD_LEFT);
        $numbers = array_map('intval', str_split(substr($digits, 0, 9)));
        $numbers[] = $this->calculateVerifier($numbers);
        $numbers[] = $this->calculateVerifier($numbers);

        return implode('', $numbers);
    }

    /**
     * @param array<int, int> $digits
     */
    private function calculateVerifier(array $digits): int
    {
        $factor = count($digits) + 1;
        $sum = 0;

        foreach ($digits as $index => $digit) {
            $sum += $digit * ($factor - $index);
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}

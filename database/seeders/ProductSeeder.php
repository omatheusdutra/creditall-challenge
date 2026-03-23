<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();

        Product::query()->upsert(
            array_map(static fn (array $product): array => [
                ...$product,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $this->catalog()),
            ['name'],
            ['description', 'price', 'updated_at'],
        );
    }

    /**
     * @return array<int, array{name: string, description: string, price: string, image_path: null}>
     */
    private function catalog(): array
    {
        return [
            [
                'name' => 'Apple MacBook Air 13" M3 16GB 512GB',
                'description' => 'Notebook ultraleve para executivos e equipes distribuídas, com chip Apple M3, 16GB de memória unificada e SSD de 512GB.',
                'price' => '11999.00',
                'image_path' => null,
            ],
            [
                'name' => 'Dell Latitude 5440 i7 16GB 512GB',
                'description' => 'Notebook corporativo da linha Latitude com Intel Core i7, 16GB de RAM, SSD de 512GB e construção voltada ao ambiente empresarial.',
                'price' => '8799.00',
                'image_path' => null,
            ],
            [
                'name' => 'Lenovo ThinkPad E14 Gen 5 Ryzen 7 16GB 512GB',
                'description' => 'Notebook empresarial com processador AMD Ryzen 7, 16GB de RAM, SSD de 512GB e perfil de durabilidade padrão MIL-STD.',
                'price' => '6299.00',
                'image_path' => null,
            ],
            [
                'name' => 'Samsung Galaxy S24 256GB',
                'description' => 'Smartphone premium com 256GB de armazenamento, sistema avançado de câmeras e ciclo de vida ideal para mobilidade corporativa.',
                'price' => '5299.00',
                'image_path' => null,
            ],
            [
                'name' => 'Apple iPhone 15 128GB',
                'description' => 'Smartphone de alto padrão com 128GB de armazenamento, forte integração de ecossistema e amplo suporte para uso executivo.',
                'price' => '5799.00',
                'image_path' => null,
            ],
            [
                'name' => 'Dell UltraSharp U2723QE 27" 4K USB-C Monitor',
                'description' => 'Monitor 4K de 27 polegadas com hub USB-C, alta fidelidade de cores e base ergonômica para estações de trabalho modernas.',
                'price' => '4299.00',
                'image_path' => null,
            ],
            [
                'name' => 'Samsung Smart Monitor M8 32" 4K',
                'description' => 'Monitor 4K de 32 polegadas com recursos inteligentes, videochamadas integradas e conectividade pensada para equipes híbridas.',
                'price' => '3399.00',
                'image_path' => null,
            ],
            [
                'name' => 'Logitech MX Keys S Keyboard',
                'description' => 'Teclado sem fio premium focado em produtividade, digitação confortável e conectividade com múltiplos dispositivos.',
                'price' => '799.00',
                'image_path' => null,
            ],
            [
                'name' => 'Logitech MX Master 3S Mouse',
                'description' => 'Mouse ergonômico para produtividade com rolagem MagSpeed, cliques silenciosos e suporte a fluxo entre dispositivos.',
                'price' => '599.00',
                'image_path' => null,
            ],
            [
                'name' => 'Apple iPad Air 11" M2 256GB Wi-Fi',
                'description' => 'Tablet leve para produtividade executiva, apresentações e mobilidade em campo, equipado com chip Apple M2.',
                'price' => '7199.00',
                'image_path' => null,
            ],
            [
                'name' => 'Epson EcoTank L4260 Multifunction Printer',
                'description' => 'Impressora multifuncional econômica com sistema de tanque de tinta, impressão duplex e conectividade Wi-Fi para escritório.',
                'price' => '1899.00',
                'image_path' => null,
            ],
            [
                'name' => 'HP LaserJet Pro 4003dn Network Printer',
                'description' => 'Impressora laser monocromática profissional com suporte de rede, alto volume de impressão e baixa sobrecarga operacional.',
                'price' => '2599.00',
                'image_path' => null,
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Cpf;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_it_sanitizes_and_masks_a_cpf(): void
    {
        $this->assertSame('52998224725', Cpf::sanitize('529.982.247-25'));
        $this->assertSame('***.***.***-25', Cpf::mask('529.982.247-25'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    public function test_dashboard_page_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Painel de controle para cat&aacute;logo, clientes e vendas.', false);
    }
}

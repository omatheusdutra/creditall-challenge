<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $this->configureParallelTestingDatabaseCredentials();

        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    private function configureParallelTestingDatabaseCredentials(): void
    {
        if (! $this->isParallelDockerRun()) {
            return;
        }

        $parallelUsername = getenv('DB_PARALLEL_USERNAME') ?: 'root';
        $parallelPassword = getenv('DB_PARALLEL_PASSWORD')
            ?: getenv('DB_ROOT_PASSWORD')
            ?: 'root';

        $this->setEnvironmentValue('DB_USERNAME', $parallelUsername);
        $this->setEnvironmentValue('DB_PASSWORD', $parallelPassword);
    }

    private function isParallelDockerRun(): bool
    {
        $testToken = getenv('TEST_TOKEN');
        $databaseHost = getenv('DB_HOST');

        return $testToken !== false
            && $testToken !== ''
            && $databaseHost === 'mysql';
    }

    private function setEnvironmentValue(string $name, string $value): void
    {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

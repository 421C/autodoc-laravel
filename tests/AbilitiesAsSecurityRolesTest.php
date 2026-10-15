<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests;

use AutoDoc\Laravel\Providers\AutoDocServiceProvider;
use AutoDoc\Laravel\Tests\TestProject\TestRouteProvider;
use AutoDoc\Laravel\Tests\Traits\ReadsGeneratedSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AbilitiesAsSecurityRolesTest extends TestCase
{
    use RefreshDatabase, ReadsGeneratedSchema;

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            AutoDocServiceProvider::class,
            TestRouteProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('autodoc.laravel.abilities_as_security_roles', false);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/TestProject/migrations');
    }


    #[Test]
    public function abilitiesAreNotListedAsRolesWhileTheRequirementStays(): void
    {
        $operation = $this->getOperation('/test/auth/sanctum-abilities', 'post');

        $this->assertSame([['sanctum' => []]], $operation['security'] ?? null);
        $this->assertArrayHasKey(403, $this->digArray($operation, ['responses']));
    }
}

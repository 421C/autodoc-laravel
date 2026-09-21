<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests;

use AutoDoc\Laravel\Extensions\EloquentModel;
use AutoDoc\Laravel\Providers\AutoDocServiceProvider;
use AutoDoc\Laravel\Tests\TestProject\TestRouteProvider;
use AutoDoc\Laravel\Tests\Traits\ReadsGeneratedSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

class UnknownBuilderMethodTest extends \Orchestra\Testbench\TestCase
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
        $app['config']->set('autodoc.laravel.abandon_query_builder_parsing_on_unknown_methods', true);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/TestProject/migrations');
    }

    /**
     * @param  \Illuminate\Routing\Router  $router
     */
    protected function defineRoutes($router)
    {
        Route::get('/test/unknown-methods/local-scope', [TestProject\Http\UnknownBuilderMethodController::class, 'localScope']);
        Route::get('/test/unknown-methods/model-static', [TestProject\Http\UnknownBuilderMethodController::class, 'modelStaticMethod']);
        Route::get('/test/unknown-methods/unknown', [TestProject\Http\UnknownBuilderMethodController::class, 'unknownMethod']);
        Route::get('/test/unknown-methods/database-connection', [TestProject\Http\UnknownBuilderMethodController::class, 'databaseConnection']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        EloquentModel::clearCache();
    }

    protected function tearDown(): void
    {
        EloquentModel::clearCache();

        parent::tearDown();
    }


    #[Test]
    public function aLocalScopeDoesNotAbandonTheChain(): void
    {
        $itemProperties = $this->getResponseItemProperties('/test/unknown-methods/local-scope');

        $this->assertArrayHasKey('name', $itemProperties);
        $this->assertArrayHasKey('diameter', $itemProperties);
    }


    #[Test]
    public function aModelStaticMethodDoesNotAbandonTheChain(): void
    {
        $itemProperties = $this->getResponseItemProperties('/test/unknown-methods/model-static');

        $this->assertArrayHasKey('name', $itemProperties);
        $this->assertArrayHasKey('diameter', $itemProperties);
    }


    #[Test]
    public function aDatabaseManagerMethodDoesNotAbandonTheChain(): void
    {
        $itemProperties = $this->getResponseItemProperties('/test/unknown-methods/database-connection');

        $this->assertArrayHasKey('name', $itemProperties);
        $this->assertArrayHasKey('diameter', $itemProperties);
    }


    /**
     * The builder's own `Collection` return type survives; only the row shape
     * inference is abandoned.
     */
    #[Test]
    public function aGenuinelyUnknownMethodStillAbandonsRowInference(): void
    {
        $responseSchema = $this->getResponseSchema('/test/unknown-methods/unknown');

        $itemSchema = $this->digArray($responseSchema, ['items']);

        $this->assertArrayNotHasKey('properties', $itemSchema);
    }


    /**
     * @return array<string, mixed>
     */
    private function getResponseItemProperties(string $uri): array
    {
        $responseSchema = $this->getResponseSchema($uri);

        $this->assertSame('array', $responseSchema['type'] ?? null);

        return $this->digArray($responseSchema, ['items', 'properties']);
    }
}

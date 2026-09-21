<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests;

use AutoDoc\Laravel\Extensions\EloquentModel;
use AutoDoc\Laravel\Providers\AutoDocServiceProvider;
use AutoDoc\Laravel\Tests\TestProject\TestRouteProvider;
use AutoDoc\Laravel\Tests\Traits\ReadsGeneratedSchema;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

/**
 * When `laravel.offline_mode` is enabled the package must build model attribute
 * shapes without ever connecting to the database. This suite deliberately loads
 * no migrations (and points at an unusable connection) so any DB access throws.
 */
class OfflineModeTest extends \Orchestra\Testbench\TestCase
{
    use ReadsGeneratedSchema;

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
        $app['config']->set('autodoc.laravel.offline_mode', true);

        // A connection that would fail if anything actually tried to use it.
        $app['config']->set('database.default', 'autodoc_offline');
        $app['config']->set('database.connections.autodoc_offline', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 1,
            'database' => 'autodoc_offline_missing',
            'username' => 'autodoc_offline_missing',
            'password' => 'autodoc_offline_missing',
        ]);
    }

    /**
     * @param  \Illuminate\Routing\Router  $router
     */
    protected function defineRoutes($router)
    {
        Route::get('/test/offline/planet', [TestProject\Http\OfflineModeController::class, 'showPlanet']);
        Route::get('/test/offline/attributed-record', [TestProject\Http\OfflineModeController::class, 'showAttributedRecord']);
        Route::get('/test/offline/raw-table', [TestProject\Http\OfflineModeController::class, 'rawTableQuery']);
        Route::get('/test/offline/joined-model', [TestProject\Http\OfflineModeController::class, 'joinedModelQuery']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Static analyzer caches persist across test classes in one process and
        // would otherwise leak DB-derived types from earlier DB-backed suites.
        EloquentModel::clearCache();
    }

    protected function tearDown(): void
    {
        // Also clear on the way out so the column-less offline shapes built here
        // do not leak into subsequent DB-backed suites.
        EloquentModel::clearCache();

        parent::tearDown();
    }

    #[Test]
    public function modelShapesAreBuiltWithoutDatabaseAccess(): void
    {
        $properties = $this->getResponseProperties('/test/offline/planet');

        // Attributes Eloquent reports in-memory (casts + dates) survive offline
        // without any database access.
        $this->assertArrayHasKey('visited', $properties);
        $this->assertSame('boolean', $this->digArray($properties, ['visited'])['type'] ?? null);

        $this->assertArrayHasKey('id', $properties);
        $this->assertSame('integer', $this->digArray($properties, ['id'])['type'] ?? null);

        $this->assertArrayHasKey('created_at', $properties);
        $this->assertSame('date-time', $this->digArray($properties, ['created_at'])['format'] ?? null);

        // `name` and `diameter` exist only in the migration/database schema, so
        // with no DB introspection they must be absent.
        $this->assertArrayNotHasKey('name', $properties);
        $this->assertArrayNotHasKey('diameter', $properties);
    }


    #[Test]
    public function attributeConfiguredPrimaryKeyResolvesWithoutDatabaseAccess(): void
    {
        $properties = $this->getResponseProperties('/test/offline/attributed-record');

        // A non-incrementing custom primary key is absent from Eloquent's own
        // casts, so offline mode must derive it from the key type instead.
        $this->assertSame('string', $this->digArray($properties, ['uuid'])['type'] ?? null);

        // #[WithoutTimestamps] leaves no date attributes, and there are no
        // columns offline, so the timestamp attributes must be absent.
        $this->assertArrayNotHasKey('created_at', $properties);
        $this->assertArrayNotHasKey('updated_at', $properties);
    }


    /**
     * A raw table query has no casts, appends or PHPDoc to fall back on, so
     * without schema access there is nothing left to describe its rows.
     */
    #[Test]
    public function aRawTableQueryHasNoRowShapeWithoutDatabaseAccess(): void
    {
        $responseSchema = $this->getResponseSchema('/test/offline/raw-table');

        $this->assertSame('array', $responseSchema['type'] ?? null);
        $this->assertArrayNotHasKey('properties', $this->digArray($responseSchema, ['items']));
    }


    #[Test]
    public function aJoinedTableContributesNothingWithoutDatabaseAccess(): void
    {
        $responseSchema = $this->getResponseSchema('/test/offline/joined-model');

        $properties = $this->digArray($responseSchema, ['items', 'properties']);

        $this->assertSame('boolean', $this->digArray($properties, ['visited'])['type'] ?? null);

        $this->assertArrayNotHasKey('launch_date', $properties);
        $this->assertArrayNotHasKey('target_planet_id', $properties);
    }


    /**
     * Generated 200 response properties of the given GET operation.
     *
     * @return array<string, mixed>
     */
    private function getResponseProperties(string $uri): array
    {
        $responseSchema = $this->getResponseSchema($uri);

        $this->assertSame('object', $responseSchema['type'] ?? null);

        return $this->digArray($responseSchema, ['properties']);
    }
}

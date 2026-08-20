<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests;

use AutoDoc\Laravel\ConfigLoader;
use AutoDoc\Laravel\Extensions\EloquentModel;
use AutoDoc\Laravel\Providers\AutoDocServiceProvider;
use AutoDoc\Laravel\Tests\TestProject\TestRouteProvider;
use AutoDoc\Workspace;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

/**
 * When `laravel.offline_mode` is enabled the package must build model attribute
 * shapes without ever connecting to the database. This suite deliberately loads
 * no migrations (and points at an unusable connection) so any DB access throws.
 *
 * @phpstan-type Schema array{
 *     paths: array<string, array<string, array<string, mixed>>>,
 * }
 */
class OfflineModeTest extends \Orchestra\Testbench\TestCase
{
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
     * Generated 200 response properties of the given GET operation.
     *
     * @return array<string, mixed>
     */
    private function getResponseProperties(string $uri): array
    {
        $config = (new ConfigLoader)->load();

        $workspace = Workspace::getDefault($config);

        $this->assertNotNull($workspace);

        /** @var ?Schema */
        $schema = json_decode($workspace->getJson() ?: '', true);

        $this->assertNotNull($schema);

        /** @var array<string, mixed> */
        $operation = $schema['paths'][$uri]['get'] ?? [];

        $responseSchema = $this->digArray($operation, ['responses', 200, 'content', 'application/json', 'schema']);

        $this->assertSame('object', $responseSchema['type'] ?? null);

        return $this->digArray($responseSchema, ['properties']);
    }

    /**
     * Walk a nested schema array by keys, returning an empty array on any miss.
     *
     * @param array<string, mixed> $array
     * @param array<int, string|int> $keys
     * @return array<string, mixed>
     */
    private function digArray(array $array, array $keys): array
    {
        $value = $array;

        foreach ($keys as $key) {
            if (! is_array($value) || ! array_key_exists($key, $value)) {
                return [];
            }

            $value = $value[$key];
        }

        return is_array($value) ? $value : [];
    }
}

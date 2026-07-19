<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests;

use AutoDoc\Laravel\ConfigLoader;
use AutoDoc\Laravel\Providers\AutoDocServiceProvider;
use AutoDoc\Laravel\Tests\TestProject\Extensions\ProjectExceptionResponses;
use AutoDoc\Laravel\Tests\TestProject\TestRouteProvider;
use AutoDoc\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Proves a project that overrides Laravel's exception rendering can correct the
 * built-in error-response shapes with its own operation extension.
 */
class ConsumerErrorResponseOverrideTest extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            AutoDocServiceProvider::class,
            TestRouteProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/TestProject/migrations');
    }

    #[Test]
    public function consumerExtensionRewritesBuiltInErrorResponses(): void
    {
        $envelope = [
            'type' => 'object',
            'properties' => [
                'error' => ['type' => 'string'],
                'status' => ['type' => 'integer'],
            ],
            'required' => ['error', 'status'],
        ];

        // Without the consumer extension the built-in emits a bodyless response
        // (only the status code, since projects routinely override the shape).
        $default = $this->generatePaths([]);

        $this->assertSame(
            ['description' => ''],
            $this->dig($default, 'paths', '/test/eloquent/sole-finisher', 'post', 'responses', 404),
        );

        // With the consumer extension registered, both the 404 (from the Eloquent
        // `sole()` finisher) and the 422 (from validation) are rewritten.
        $overridden = $this->generatePaths([ProjectExceptionResponses::class]);

        $this->assertSame(
            $envelope,
            $this->dig($overridden, 'paths', '/test/eloquent/sole-finisher', 'post', 'responses', 404, 'content', 'application/json', 'schema'),
        );

        $this->assertSame(
            $envelope,
            $this->dig($overridden, 'paths', '/test/request-params/all-after-validation', 'post', 'responses', 422, 'content', 'application/json', 'schema'),
        );

        // The success response is untouched by the error-only override.
        $this->assertSame(
            'object',
            $this->dig($overridden, 'paths', '/test/eloquent/sole-finisher', 'post', 'responses', 200, 'content', 'application/json', 'schema', 'type'),
        );
    }


    private function dig(mixed $value, int|string ...$keys): mixed
    {
        foreach ($keys as $key) {
            $this->assertIsArray($value);
            $this->assertArrayHasKey($key, $value);

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * @param list<class-string> $extensions
     */
    private function generatePaths(array $extensions): mixed
    {
        $exportDir = sys_get_temp_dir() . '/autodoc-override-' . uniqid();
        mkdir($exportDir, 0777, true);

        config()->set('autodoc.extensions', $extensions);
        config()->set('autodoc.use_cache', false);
        config()->set('autodoc.openapi_export_dir', $exportDir);

        $config = (new ConfigLoader)->load();
        $workspace = Workspace::getDefault($config);

        $this->assertNotNull($workspace);

        return json_decode($workspace->getJson() ?: '{}', true);
    }
}

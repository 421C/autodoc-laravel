<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests;

use AutoDoc\Laravel\ConfigLoader;
use AutoDoc\Laravel\Providers\AutoDocServiceProvider;
use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\TestRouteProvider;
use AutoDoc\Laravel\Tests\Traits\ComparesSchemaArrays;
use AutoDoc\Workspace;
use Closure;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionFunction;

/**
 * @phpstan-type Schema array{
 *     paths: array<string, array<string, array<string, mixed>>>,
 * }
 */
class OpenApiSchemaTest extends \Orchestra\Testbench\TestCase
{
    use ComparesSchemaArrays;

    /**
     * Get package providers.
     *
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


    #[Test]
    public function checkOpenApiJsonSchema(): void
    {
        $config = (new ConfigLoader)->load();

        $workspace = Workspace::getDefault($config);

        $this->assertNotNull($workspace);

        /** @var ?Schema */
        $schema = json_decode($workspace->getJson() ?: '', true);

        $this->assertNotNull($schema);

        $routeLoader = $config->getRouteLoader();
        $routes = $routeLoader->getRoutes();

        foreach ($routes as $route) {
            $route->uri = '/' . ltrim($route->uri, '/');
            $route->method = strtolower($route->method);

            if (! $routeLoader->isRouteAllowed($route)) {
                continue;
            }

            if ($route->className && $route->classMethod) {
                $this->assertClassMethodMatchesOperationSchema(
                    schema: $schema,
                    className: $route->className,
                    classMethod: $route->classMethod,
                    uri: $route->uri,
                    method: $route->method,
                );

            } else if ($route->closure) {
                $this->assertClosureMatchesOperationSchema(
                    schema: $schema,
                    closure: $route->closure,
                    uri: $route->uri,
                    method: $route->method,
                );

            } else {
                $this->fail();
            }
        }
    }


    /**
     * @param Schema $schema
     * @param class-string $className
     */
    private function assertClassMethodMatchesOperationSchema(array $schema, string $className, string $classMethod, string $uri, string $method): void
    {
        $this->assertTrue(isset($schema['paths'][$uri][$method]), 'Operation schema for ' . strtoupper($method) . ' ' . $uri . '" not found.');

        $reflectionClass = new ReflectionClass($className);

        $expectedSchemaAttribute = $reflectionClass->getMethod($classMethod)->getAttributes(ExpectedOperationSchema::class)[0] ?? null;


        /** @var array<string, mixed> */
        $expected = $expectedSchemaAttribute?->getArguments()[0] ?? [];

        /** @var array<string, mixed> */
        $actual = $schema['paths'][$uri][$method];


        $this->assertSchemaArraysMatch($expected, $actual, $uri, $method);
    }


    /**
     * @param Schema $schema
     */
    private function assertClosureMatchesOperationSchema(array $schema, Closure $closure, string $uri, string $method): void
    {
        $reflectionFunction = new ReflectionFunction($closure);

        $expectedSchemaAttribute = $reflectionFunction->getAttributes(ExpectedOperationSchema::class)[0] ?? null;

        /** @var array<string, mixed> */
        $expected = $expectedSchemaAttribute?->getArguments()[0] ?? [];

        /** @var array<string, mixed> */
        $actual = $schema['paths'][$uri][$method];


        $this->assertSchemaArraysMatch($expected, $actual, $uri, $method);
    }
}

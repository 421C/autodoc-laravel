<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\Traits;

use AutoDoc\Laravel\ConfigLoader;
use AutoDoc\Workspace;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-require-extends TestCase
 *
 * @phpstan-type Schema array{
 *     paths: array<string, array<string, array<string, mixed>>>,
 * }
 */
trait ReadsGeneratedSchema
{
    /**
     * The 200 `application/json` response schema of the generated GET operation.
     *
     * @return array<string, mixed>
     */
    protected function getResponseSchema(string $uri): array
    {
        $config = (new ConfigLoader)->load();

        $workspace = Workspace::getDefault($config);

        $this->assertNotNull($workspace);

        /** @var ?Schema */
        $schema = json_decode($workspace->getJson() ?: '', true);

        $this->assertNotNull($schema);

        /** @var array<string, mixed> */
        $operation = $schema['paths'][$uri]['get'] ?? [];

        return $this->digArray($operation, ['responses', 200, 'content', 'application/json', 'schema']);
    }

    /**
     * Walk a nested schema array by keys, returning an empty array on any miss.
     *
     * @param array<string, mixed> $array
     * @param array<int, string|int> $keys
     * @return array<string, mixed>
     */
    protected function digArray(array $array, array $keys): array
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

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests;

use AutoDoc\Analyzer\Scope;
use AutoDoc\Laravel\ConfigLoader;
use AutoDoc\Laravel\Providers\AutoDocServiceProvider;
use AutoDoc\Laravel\Tests\TestProject\TestRouteProvider;
use AutoDoc\TypeScript\TypeScriptFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class TypeScriptSchemaTest extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

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

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/TestProject/migrations');
    }

    #[Test]
    public function dbModel(): void
    {
        $this->assertTypeScriptGeneratedCorrectly(
            input: '
            /**
             * @autodoc AutoDoc\Laravel\Tests\TestProject\Models\Planet
             */
            interface Planet {}',
            expected: '
            /**
             * @autodoc AutoDoc\Laravel\Tests\TestProject\Models\Planet
             */
            interface Planet {
                id: number
                name: string
                diameter: number
                visited: boolean
                created_at: string|null
                updated_at: string|null
            }',
        );
    }


    #[Test]
    public function dbModelToArray(): void
    {
        $this->assertTypeScriptGeneratedCorrectly(
            input: '
            /** @autodoc AutoDoc\Laravel\Tests\TestProject\Models\Rocket */
            ',
            expected: '
            /** @autodoc AutoDoc\Laravel\Tests\TestProject\Models\Rocket */
            type Rocket = {
                id: number
                name: string
            }
            ',
        );
    }


    #[Test]
    public function queryResultResponse(): void
    {
        $this->assertTypeScriptGeneratedCorrectly(
            input: '
            /** @autodoc Post /test/eloquent/all-alias */
            ',
            expected: '
            /** @autodoc Post /test/eloquent/all-alias */
            type AllAliasResponse = Array<{
                planet_name: string
                diameter: number
            }>
            ',
        );
    }


    #[Test]
    public function modelWithOmitAndExtraData(): void
    {
        $this->assertTypeScriptGeneratedCorrectly(
            input: <<<EOS
            /**
             * @autodoc AutoDoc\Laravel\Tests\TestProject\Models\Planet {
             *     omit: 'created_at' | 'updated_at',
             *     with: object{
             *         radius: float,
             *     }
             * }
             */
            EOS,
            expected: <<<EOS
            /**
             * @autodoc AutoDoc\Laravel\Tests\TestProject\Models\Planet {
             *     omit: 'created_at' | 'updated_at',
             *     with: object{
             *         radius: float,
             *     }
             * }
             */
            type Planet = {
                id: number
                name: string
                diameter: number
                visited: boolean
                radius: number
            }
            EOS,
        );
    }


    #[Test]
    public function orderEndpointRequestResponse(): void
    {
        $this->assertTypeScriptGeneratedCorrectly(
            input: '
            /** @autodoc patch /test/orders/{order} request */
            /** @autodoc patch /test/orders/{order} */
            /** @autodoc patch /test/orders/{order} 400 */
            type OrderResponseBadRequest = unknown
            ',
            expected: '
            /** @autodoc patch /test/orders/{order} request */
            type OrderRequest = {
                status: 1|2|3|4
            }
            /** @autodoc patch /test/orders/{order} */
            type OrderResponse = {
                status_updated: true
                products: Array<{
                    id: number
                    name: string
                }>
            }
            /** @autodoc patch /test/orders/{order} 400 */
            type OrderResponseBadRequest = {
                status_updated: false
                message: \'Completed orders can’t be changed\'
                order: {
                    id: number
                    status: 1|2|3|4
                    created_at?: string
                    updated_at?: string
                }
            }
            ',
        );
    }


    private function assertTypeScriptGeneratedCorrectly(string $input, string $expected): void
    {
        $scope = new Scope((new ConfigLoader)->load());
        $tsFile = new TypeScriptFile;

        $tsFile->lines = explode("\n", str_replace("\r\n", "\n", $input));

        $tsFile->processAutodocTags($scope);

        $result = implode("\n", $tsFile->lines);
        $expected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($expected, $result);
    }
}

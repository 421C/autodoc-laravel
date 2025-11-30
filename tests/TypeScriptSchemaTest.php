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
            /** @autodoc GET /test/29 */
            ',
            expected: '
            /** @autodoc GET /test/29 */
            type 29Response = Array<{
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

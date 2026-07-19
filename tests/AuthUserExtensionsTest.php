<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Laravel\ConfigLoader;
use AutoDoc\Laravel\Extensions\AuthUserMethodCall;
use AutoDoc\Laravel\Helpers\AuthGuardSelection;
use AutoDoc\Laravel\Helpers\AuthUserTypeResolver;
use AutoDoc\Laravel\Providers\AutoDocServiceProvider;
use AutoDoc\Laravel\Tests\TestProject\Models\AdminUser;
use AutoDoc\Laravel\Tests\TestProject\Models\User;
use AutoDoc\Laravel\Tests\TestProject\TestRouteProvider;
use AutoDoc\Route;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\Attributes\Test;

class AuthUserExtensionsTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            AutoDocServiceProvider::class,
            TestRouteProvider::class,
        ];
    }


    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/TestProject/migrations');
    }


    #[Test]
    public function dynamicExplicitGuardDefersToDefaultAnalysis(): void
    {
        $scope = $this->makeScope();
        $guardExpression = new FuncCall(
            name: new Name('config'),
            args: [new Arg(new String_('runtime.guard'))],
        );
        $context = $this->makeAuthUserContext($guardExpression, $scope);

        self::assertNull((new AuthUserMethodCall)->getReturnType($context));
    }


    #[Test]
    public function finiteLiteralGuardUnionResolvesEveryModel(): void
    {
        $scope = $this->makeScope();
        $guardExpression = new Ternary(
            cond: new FuncCall(
                name: new Name('random_int'),
                args: [
                    new Arg(new Int_(0)),
                    new Arg(new Int_(1)),
                ],
            ),
            if: new String_('admin'),
            else: new String_('web'),
        );
        $context = $this->makeAuthUserContext($guardExpression, $scope);
        $type = (new AuthUserMethodCall)->getReturnType($context);

        self::assertInstanceOf(UnionType::class, $type);

        $classNames = [];
        $hasNull = false;

        foreach ($type->types as $variantType) {
            if ($variantType instanceof ObjectType && $variantType->className !== null) {
                $classNames[] = $variantType->className;
            }

            $hasNull = $hasNull || $variantType instanceof NullType;
        }

        self::assertEqualsCanonicalizing([AdminUser::class, User::class], $classNames);
        self::assertTrue($hasNull);
    }


    #[Test]
    public function providerWithoutModelDefersToDefaultAnalysis(): void
    {
        $type = (new AuthUserTypeResolver)->resolveUserType(
            $this->makeScope(),
            AuthGuardSelection::explicit(['database']),
        );

        self::assertNull($type);
    }


    #[Test]
    public function basicAuthFieldWithoutGuardUsesAndGuaranteesTheDefaultGuard(): void
    {
        $type = (new AuthUserTypeResolver)->resolveUserIdType(
            $this->makeScope([AuthenticateWithBasicAuth::class . ':,username']),
            AuthGuardSelection::implicit(),
        );

        self::assertInstanceOf(IntegerType::class, $type);
    }


    /**
     * @param list<string> $middleware
     */
    private function makeScope(array $middleware = []): Scope
    {
        return new Scope(
            config: (new ConfigLoader)->load(),
            route: new Route(
                uri: '/test/auth',
                method: 'get',
                meta: ['middleware' => $middleware],
            ),
        );
    }


    private function makeAuthUserContext(Expr $guardExpression, Scope $scope): MethodCallContext
    {
        return new MethodCallContext(
            node: new MethodCall(
                var: new FuncCall(
                    name: new Name('auth'),
                    args: [new Arg($guardExpression)],
                ),
                name: 'user',
            ),
            scope: $scope,
        );
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnresolvedClassType;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final class AuthUserTypeResolver
{
    public function resolveUserType(Scope $scope, AuthGuardSelection $selection): ?Type
    {
        $modelClassNames = $this->resolveUserModelClassNames($selection, $scope);

        if ($modelClassNames === null) {
            return null;
        }

        $types = array_map(
            fn (string $modelClassName): Type => new UnresolvedClassType(
                className: $modelClassName,
                scope: $scope,
            ),
            $modelClassNames,
        );

        return $this->nullableWhenNotGuaranteed(
            $this->unionTypes($types, $scope),
            $selection,
            $scope,
        );
    }


    public function resolveUserIdType(Scope $scope, AuthGuardSelection $selection): ?Type
    {
        $modelClassNames = $this->resolveUserModelClassNames($selection, $scope);

        if ($modelClassNames === null) {
            return null;
        }

        $types = array_map($this->userModelKeyType(...), $modelClassNames);

        return $this->nullableWhenNotGuaranteed(
            $this->unionTypes($types, $scope),
            $selection,
            $scope,
        );
    }


    /**
     * @param non-empty-list<Type> $types
     */
    private function unionTypes(array $types, Scope $scope): Type
    {
        if (count($types) === 1) {
            return $types[0];
        }

        return new UnionType($types)->unwrapType($scope->config);
    }


    private function nullableWhenNotGuaranteed(Type $type, AuthGuardSelection $selection, Scope $scope): Type
    {
        if ($this->routeGuaranteesAuthenticatedUser($selection, $scope)) {
            return $type;
        }

        return new UnionType([$type, new NullType])->unwrapType($scope->config);
    }


    /**
     * @return non-empty-list<class-string>|null
     */
    private function resolveUserModelClassNames(AuthGuardSelection $selection, Scope $scope): ?array
    {
        $guardNames = $selection->isImplicit()
            ? $this->routeAuthState($scope)['implicitGuardNames']
            : $selection->guardNames;

        if ($guardNames === null || $guardNames === []) {
            return null;
        }

        $modelClassNames = [];

        foreach ($guardNames as $guardName) {
            $providerName = $this->authConfigString('auth.guards.' . $guardName . '.provider');

            if ($providerName === null) {
                return null;
            }

            $modelClassName = $this->authConfigString('auth.providers.' . $providerName . '.model');

            if ($modelClassName === null
                || ! class_exists($modelClassName)
                || ! is_a($modelClassName, Authenticatable::class, true)
            ) {
                return null;
            }

            $modelClassNames[] = $modelClassName;
        }

        return array_values(array_unique($modelClassNames));
    }


    private function userModelKeyType(string $modelClassName): Type
    {
        try {
            $model = app()->make($modelClassName);

        } catch (Throwable) {
            $model = null;
        }

        if ($model instanceof Model) {
            return $model->getKeyType() === 'int' ? new IntegerType : new StringType;
        }

        return new UnionType([new IntegerType, new StringType]);
    }


    private function routeGuaranteesAuthenticatedUser(AuthGuardSelection $selection, Scope $scope): bool
    {
        $routeAuthState = $this->routeAuthState($scope);

        if ($selection->isImplicit()) {
            return $routeAuthState['implicitUserGuaranteed'];
        }

        foreach ($selection->guardNames ?? [] as $guardName) {
            if (! in_array($guardName, $routeAuthState['guaranteedGuardNames'], true)) {
                return false;
            }
        }

        return $selection->guardNames !== [];
    }


    /**
     * @return array{
     *     implicitGuardNames: list<string>,
     *     guaranteedGuardNames: list<string>,
     *     implicitUserGuaranteed: bool,
     * }
     */
    private function routeAuthState(Scope $scope): array
    {
        $defaultGuardName = $this->authConfigString('auth.defaults.guard');
        $implicitGuardNames = $defaultGuardName === null ? [] : [$defaultGuardName];
        $guaranteedGuardNames = [];
        $implicitUserGuaranteed = false;

        foreach ($this->routeMiddleware($scope) as $middleware) {
            if ($this->isBasicAuthMiddleware($middleware)) {
                $parameterSlots = $this->middlewareParameterSlots($middleware);
                $usesImplicitGuard = $parameterSlots === [] || $parameterSlots[0] === '';
                $basicGuardNames = $usesImplicitGuard ? $implicitGuardNames : [$parameterSlots[0]];

                if (count($basicGuardNames) === 1) {
                    $guaranteedGuardNames[] = $basicGuardNames[0];
                }

                if ($usesImplicitGuard || $basicGuardNames === $implicitGuardNames) {
                    $implicitUserGuaranteed = $implicitGuardNames !== [];
                }

            } else if ($this->isAuthenticateMiddleware($middleware)) {
                $parameterSlots = $this->middlewareParameterSlots($middleware);
                $guardNames = [];

                foreach ($parameterSlots as $parameterSlot) {
                    if ($parameterSlot === '') {
                        array_push($guardNames, ...$implicitGuardNames);

                    } else {
                        $guardNames[] = $parameterSlot;
                    }
                }

                if ($guardNames !== []) {
                    $implicitGuardNames = array_values(array_unique($guardNames));
                }

                if ($implicitGuardNames !== []) {
                    $implicitUserGuaranteed = true;

                    if (count($implicitGuardNames) === 1) {
                        $guaranteedGuardNames[] = $implicitGuardNames[0];
                    }
                }
            }
        }

        return [
            'implicitGuardNames' => array_values(array_unique($implicitGuardNames)),
            'guaranteedGuardNames' => array_values(array_unique($guaranteedGuardNames)),
            'implicitUserGuaranteed' => $implicitUserGuaranteed,
        ];
    }


    /**
     * @return list<string>
     */
    private function routeMiddleware(Scope $scope): array
    {
        $middleware = $scope->route?->meta['middleware'] ?? [];

        if (! is_array($middleware)) {
            return [];
        }

        return array_values(array_filter($middleware, 'is_string'));
    }


    private function isAuthenticateMiddleware(string $middleware): bool
    {
        $className = explode(':', $middleware, 2)[0];

        return strtolower($className) === 'auth'
            || is_a($className, Authenticate::class, true);
    }


    private function isBasicAuthMiddleware(string $middleware): bool
    {
        $className = explode(':', $middleware, 2)[0];

        return strtolower($className) === 'auth.basic'
            || is_a($className, AuthenticateWithBasicAuth::class, true);
    }


    /**
     * @return list<string>
     */
    private function middlewareParameterSlots(string $middleware): array
    {
        $parameters = explode(':', $middleware, 2)[1] ?? null;

        return $parameters === null ? [] : array_map(trim(...), explode(',', $parameters));
    }


    private function authConfigString(string $key): ?string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnresolvedClassType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;

final class AuthUserTypeResolver
{
    use InspectsModelAttributes;

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
            ? RouteMiddleware::fromRoute($scope->route)->authState()['implicitGuardNames']
            : $selection->guardNames;

        if ($guardNames === null || $guardNames === []) {
            return null;
        }

        $modelClassNames = [];

        foreach ($guardNames as $guardName) {
            $providerGuardNames = $this->providerGuardNames($guardName);

            if ($providerGuardNames === null) {
                return null;
            }

            foreach ($providerGuardNames as $providerGuardName) {
                $modelClassName = $this->guardModelClassName($providerGuardName);

                if ($modelClassName === null) {
                    return null;
                }

                $modelClassNames[] = $modelClassName;
            }
        }

        return array_values(array_unique($modelClassNames));
    }


    /**
     * @return non-empty-list<string>|null
     */
    private function providerGuardNames(string $guardName): ?array
    {
        if ($this->authConfigString('auth.guards.' . $guardName . '.provider') !== null) {
            return [$guardName];
        }

        if (config('auth.guards.' . $guardName . '.driver') !== 'sanctum') {
            return null;
        }

        $sanctumGuardNames = array_values(array_filter(Arr::wrap(config('sanctum.guard', ['web'])), 'is_string'));

        return $sanctumGuardNames === [] ? null : $sanctumGuardNames;
    }


    /**
     * @return class-string|null
     */
    private function guardModelClassName(string $guardName): ?string
    {
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

        return $modelClassName;
    }


    private function userModelKeyType(string $modelClassName): Type
    {
        $model = ModelResolver::resolve($modelClassName);

        return $model
            ? $this->modelKeyType($model)
            : new UnionType([new IntegerType, new StringType]);
    }


    private function routeGuaranteesAuthenticatedUser(AuthGuardSelection $selection, Scope $scope): bool
    {
        $routeAuthState = RouteMiddleware::fromRoute($scope->route)->authState();

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


    private function authConfigString(string $key): ?string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}

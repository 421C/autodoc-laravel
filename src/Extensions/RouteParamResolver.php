<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpClosure;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\OperationExtension;
use AutoDoc\OpenApi\Operation;
use AutoDoc\OpenApi\Parameter;
use AutoDoc\Route;
use Illuminate\Database\Eloquent\Model;
use ReflectionNamedType;
use ReflectionParameter;
use UnitEnum;

/**
 * Handles Laravel route parameters, enum and model binding.
 */
class RouteParamResolver extends OperationExtension
{
    public function handle(Operation $operation, Route $route, Scope $scope): Operation|null
    {
        $function = null;

        if ($route->className && $route->classMethod) {
            $function = $scope->getPhpClass($route->className)->getMethod($route->classMethod)->getPhpFunction();

        } else if ($route->closure) {
            $function = (new PhpClosure($route->closure, $scope))->getPhpFunction();
        }

        if (! $function) {
            return null;
        }

        $reflectionParams = $function->getReflection()->getParameters();
        $phpDocParams = $function->getPhpDoc()?->getParameters() ?? [];

        /**
         * @var list<array{
         *     name: string,
         *     binding: ?string,
         *     pattern: ?string,
         *     optional: bool,
         * }>
         */
        $pathParameters = $route->meta['pathParameters'] ?? [];

        foreach ($pathParameters as $param) {
            $type = new UnknownType;

            if (isset($phpDocParams[$param['name']])) {
                $type = $phpDocParams[$param['name']]->resolve();
            }

            $phpDocParamDescription = $type->description;
            $type->description = null;

            $reflectionType = $this->findReflectionParam($reflectionParams, $param['name'])?->getType();
            $isModel = false;

            if ($reflectionType instanceof ReflectionNamedType) {
                $typeName = $reflectionType->getName();

                if (class_exists($typeName)) {
                    $isModel = is_subclass_of($typeName, Model::class);

                    if ($isModel) {
                        /**
                         * Route model binding
                         */
                        $modelPropertyName = $param['binding'] ?? app()->make($typeName)->getRouteKeyName();

                        $type = (new EloquentModel)->getPropertyType(
                            phpClass: $scope->getPhpClass($typeName),
                            propertyName: $modelPropertyName,
                        ) ?? new UnknownType;

                        if (! $param['optional'] && $type instanceof UnionType) {
                            $type = new UnionType(array_filter($type->types, fn (Type $type) => ! $type instanceof NullType));
                        }

                    } else if (is_subclass_of($typeName, UnitEnum::class)) {
                        /**
                         * Route enum binding
                         */
                        $type = $scope->getPhpClass($typeName)->resolveType();
                    }
                }
            }

            if ($param['pattern']) {
                $pattern = "^{$param['pattern']}$";

                if ($pattern === '^[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}$') {
                    $type = new StringType(format: 'uuid');

                } else {
                    $type = new StringType(pattern: $pattern);
                }

            } else if ($reflectionType && $type instanceof UnknownType && !$isModel) {
                $type = Type::resolveFromReflection($reflectionType, $scope);
            }

            $type->addDescription($phpDocParamDescription, prepend: true);

            $operation->parameters[] = new Parameter(
                name: $param['name'],
                in: 'path',
                schema: $type->toSchema($scope->config),
                required: ! $param['optional'],
            );
        }

        return $operation;
    }


    /**
     * @param list<ReflectionParameter> $parameters
     */
    protected function findReflectionParam(array $parameters, string $name): ?ReflectionParameter
    {
        foreach ($parameters as $param) {
            if ($param->getName() === $name) {
                return $param;
            }
        }

        return null;
    }
}

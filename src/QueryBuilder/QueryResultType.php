<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\NumberType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Laravel\Helpers\ResolvesModelTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator as SimplePaginator;
use Illuminate\Support\Collection;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;

final class QueryResultType
{
    use ResolvesModelTypes;

    public function __construct(
        private Scope $scope,
        private QueryChain $chain,
        private QueryRowShape $rowShape,
    ) {}


    public function resolve(MethodCall|StaticCall $methodCall, string $methodName): ?Type
    {
        $resultType = $this->resolveFinisherType($methodCall, $methodName);

        if ($resultType && $this->chain->shortCircuitsToNull && ! $this->typeIncludesNull($resultType)) {
            return new UnionType([$resultType, new NullType]);
        }

        return $resultType;
    }


    private function resolveFinisherType(MethodCall|StaticCall $methodCall, string $methodName): ?Type
    {
        $scalarFinisherType = $this->getScalarFinisherType($methodName);

        if ($scalarFinisherType) {
            return $scalarFinisherType;
        }

        if (BuilderMethodClassifier::resolvesFromBuilderSignature($methodName)) {
            return (new BuilderMethodResolver($this->scope))->getReturnType(
                $methodName,
                ArgumentList::fromArgNodes($methodCall->args, $this->scope),
            );
        }

        $rowType = $this->scope->withoutScalarTypeValueMerging(fn () => $this->rowShape->resolveRowType());

        if (! $rowType) {
            return $this->chain->isRawDatabaseQuery ? $this->getUnshapedRowResultType($methodName) : null;
        }

        if (in_array($methodName, ['get', 'all', 'pluck'], true)) {
            return new ArrayType(
                itemType: $rowType,
                keyType: $methodName === 'pluck' ? $this->rowShape->getPluckedKeyType() : null,
                className: Collection::class,
            );
        }

        if (in_array($methodName, ['create', 'firstOrNew', 'firstOrCreate', 'updateOrCreate'])) {
            return $rowType;
        }

        if ($methodName === 'firstWhere') {
            return new UnionType([$rowType, new NullType]);
        }

        $methodArgs = ArgumentList::fromArgNodes($methodCall->args, $this->scope);

        if ($methodName === 'first') {
            return new UnionType([$this->applyColumnsArgument($rowType, $methodArgs, $methodName), new NullType]);
        }

        if ($methodName === 'firstOrFail' || $methodName === 'sole') {
            return $this->applyColumnsArgument($rowType, $methodArgs, $methodName);
        }

        if (in_array($methodName, ['value', 'min', 'max'], true)) {
            return $this->getSingleColumnFinisherType($methodArgs);
        }

        if (in_array($methodName, ['paginate', 'simplePaginate', 'cursorPaginate'], true)) {
            return $this->scope->withoutScalarTypeValueMerging(
                fn () => $this->getPaginatorType(
                    $this->applyColumnsArgument($rowType, $methodArgs, $methodName),
                    $methodArgs,
                    $methodName,
                ),
            );
        }

        if ($methodName === 'find' || $methodName === 'findOrFail') {
            return $this->getFindResultType($rowType, $methodArgs, $methodName);
        }

        return (new BuilderMethodResolver($this->scope))->getReturnType($methodName, $methodArgs);
    }


    private function getUnshapedRowResultType(string $methodName): ?Type
    {
        if ($methodName === 'get') {
            return new ArrayType(itemType: new ObjectType, className: Collection::class);
        }

        if ($methodName === 'pluck') {
            return new ArrayType(className: Collection::class);
        }

        return null;
    }


    private function getScalarFinisherType(string $methodName): ?Type
    {
        if ($methodName === 'count') {
            return new IntegerType(minimum: 0);
        }

        if ($methodName === 'exists' || $methodName === 'doesntExist') {
            return new BoolType;
        }

        if ($methodName === 'sum') {
            return new NumberType;
        }

        if ($methodName === 'avg' || $methodName === 'average') {
            return new UnionType([new NumberType, new NullType]);
        }

        return null;
    }


    private function getFindResultType(Type $rowType, ArgumentList $methodArgs, string $methodName): Type
    {
        $firstArg = $methodArgs->has(0) ? $methodArgs->get(0)->unwrapType($this->scope->config) : null;

        $multipleKeysPassed = $firstArg instanceof ArrayType
            || $firstArg instanceof ObjectType && $firstArg->typeToDisplay instanceof ArrayType;

        $rowType = $this->applyColumnsArgument($rowType, $methodArgs, $methodName);

        if ($multipleKeysPassed) {
            return new ArrayType(itemType: $rowType, className: Collection::class);
        }

        if ($methodName === 'find') {
            return new UnionType([$rowType, new NullType]);
        }

        return $rowType;
    }


    private function applyColumnsArgument(Type $rowType, ArgumentList $methodArgs, string $methodName): Type
    {
        if (! ($rowType instanceof ObjectType) || $this->rowShape->selectsExplicitColumns()) {
            return $rowType;
        }

        $builderMethod = $this->scope->getPhpClassInDeeperScope(Builder::class)->getMethod($methodName, $methodArgs);
        $columns = $this->rowShape->getColumnsFromArgument($builderMethod->getArgumentType('columns'));

        if (! $columns) {
            return $rowType;
        }

        return $this->rowShape->applyColumns($rowType, $columns, $this->rowShape->resolveEagerLoadedRelations());
    }


    private function getSingleColumnFinisherType(ArgumentList $methodArgs): ?Type
    {
        $columnTypes = $this->rowShape->resolveColumnTypesNamedByArgument($methodArgs);

        if (! $columnTypes) {
            return null;
        }

        return (new UnionType([...$columnTypes, new NullType]))->unwrapType($this->scope->config);
    }


    private function getPaginatorType(Type $rowType, ArgumentList $methodArgs, string $methodName): Type
    {
        [$paginatorClass, $pageArgName, $defaultParamName, $paramType] = match ($methodName) {
            'simplePaginate' => [SimplePaginator::class, 'pageName', 'page', new IntegerType],
            'cursorPaginate' => [CursorPaginator::class, 'cursorName', 'cursor', new StringType],
            default => [LengthAwarePaginator::class, 'pageName', 'page', new IntegerType],
        };

        $paginateMethod = $this->scope->getPhpClassInDeeperScope(Builder::class)->getMethod($methodName, $methodArgs);

        if ($this->scope->route) {
            $pageParamName = $this->resolvePageParamName($paginateMethod->getArgumentType($pageArgName), $defaultParamName);

            if ($pageParamName) {
                $this->scope->route->requestQueryParams[$pageParamName] = clone $paramType;
            }
        }

        return (new Paginator(
            paginatorPhpClass: $this->scope->getPhpClassInDeeperScope($paginatorClass),
            entryClass: $this->chain->modelClassName,
            entryType: $rowType,
        ))->resolveType();
    }


    private function resolvePageParamName(Type $pageNameType, string $defaultParamName): ?string
    {
        $pageNameType = $pageNameType->unwrapType($this->scope->config);

        if ($pageNameType instanceof StringType && is_string($pageNameType->value)) {
            return $pageNameType->value;
        }

        if ($pageNameType instanceof UnknownType) {
            return $defaultParamName;
        }

        return null;
    }
}

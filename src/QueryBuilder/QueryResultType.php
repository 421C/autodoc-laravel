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
use AutoDoc\Laravel\Helpers\ModelResolver;
use AutoDoc\Laravel\Helpers\ResolvesCallbackReturnType;
use AutoDoc\Laravel\Helpers\ResolvesModelTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator as SimplePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;

final class QueryResultType
{
    use ResolvesCallbackReturnType;
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

        $baseFinisher = BuilderMethodClassifier::finisherBehindCallbackFallback($methodName);

        if ($baseFinisher) {
            return $this->getCallbackFallbackFinisherType($methodCall, $baseFinisher);
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

        if (BuilderMethodClassifier::streamsRowsLazily($methodName)) {
            return new ArrayType(itemType: $rowType, className: LazyCollection::class);
        }

        if (in_array($methodName, ['create', 'firstOrNew', 'firstOrCreate', 'updateOrCreate'])) {
            return $rowType instanceof ObjectType
                ? $this->getWrittenModelType($rowType, ArgumentList::fromArgNodes($methodCall->args, $this->scope), $methodName)
                : $rowType;
        }

        if ($methodName === 'firstWhere') {
            return new UnionType([$rowType, new NullType]);
        }

        $methodArgs = ArgumentList::fromArgNodes($methodCall->args, $this->scope);

        if ($methodName === 'findMany') {
            return new ArrayType(
                itemType: $this->applyColumnsArgument($rowType, $methodArgs, $methodName),
                className: Collection::class,
            );
        }

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


    /**
     * Laravel returns the base finisher's result unless it found nothing, in
     * which case the callback's value takes the place of its `null`.
     */
    private function getCallbackFallbackFinisherType(MethodCall|StaticCall $methodCall, string $baseFinisher): ?Type
    {
        $baseFinisherType = $this->resolveFinisherType($methodCall, $baseFinisher);

        if (! $baseFinisherType) {
            return null;
        }

        $callbackReturnType = $this->resolveCallbackArgumentReturnType($methodCall);

        if (! $callbackReturnType) {
            return $baseFinisherType;
        }

        return $this->replaceNullVariant($baseFinisherType, $callbackReturnType);
    }


    /**
     * Both `firstOr()` and `findOr()` accept the callback either in its own
     * parameter or in the preceding `$columns` one.
     */
    private function resolveCallbackArgumentReturnType(MethodCall|StaticCall $methodCall): ?Type
    {
        $methodArgs = ArgumentList::fromArgNodes($methodCall->args, $this->scope);

        for ($index = 0; $index < count($methodArgs); $index++) {
            $callbackReturnType = $this->resolveCallbackReturnType(
                argTypes: $methodArgs,
                callbackIndex: $index,
                scope: $this->scope,
                callerNode: $methodCall,
            );

            if ($callbackReturnType) {
                return $callbackReturnType;
            }
        }

        return null;
    }


    private function replaceNullVariant(Type $baseType, Type $replacementType): Type
    {
        if ($baseType instanceof NullType) {
            return $replacementType;
        }

        if (! ($baseType instanceof UnionType)) {
            return $baseType;
        }

        $variantsWithoutNull = array_values(array_filter(
            $baseType->types,
            fn (Type $variant) => ! ($variant instanceof NullType),
        ));

        if (count($variantsWithoutNull) === count($baseType->types)) {
            return $baseType;
        }

        return (new UnionType([...$variantsWithoutNull, $replacementType]))->unwrapType($this->scope->config);
    }


    /**
     * A written model holds only what was assigned to it plus what Laravel
     * fills in, so the columns it never touched are absent. `firstOrNew` and
     * the `*OrCreate` pair may return a row that was read in full instead,
     * which makes those columns optional rather than absent.
     */
    private function getWrittenModelType(ObjectType $rowType, ArgumentList $methodArgs, string $methodName): Type
    {
        $modelClassName = $this->chain->modelClassName;
        $assignedNames = $this->resolveAssignedAttributeNames($methodArgs);
        $model = $modelClassName === null ? null : ModelResolver::resolve($modelClassName);

        if ($assignedNames === null || $model === null) {
            return $rowType;
        }

        $presentNames = array_merge(
            $assignedNames,
            array_keys($model->getAttributes()),
            $model->getAppends(),
            array_filter([
                $model->getKeyName(),
                $model->usesTimestamps() ? $model->getCreatedAtColumn() : null,
                $model->usesTimestamps() ? $model->getUpdatedAtColumn() : null,
            ]),
        );

        $writtenType = clone $rowType;

        foreach ($rowType->properties as $propertyName => $propertyType) {
            if (in_array($propertyName, $presentNames, strict: true)) {
                continue;
            }

            if ($methodName === 'create') {
                unset($writtenType->properties[$propertyName]);

            } else {
                $writtenType->properties[$propertyName] = (clone $propertyType)->setRequired(false);
            }
        }

        return $writtenType;
    }


    /**
     * @return ?list<string>
     */
    private function resolveAssignedAttributeNames(ArgumentList $methodArgs): ?array
    {
        $names = [];

        for ($index = 0; $index < count($methodArgs); $index++) {
            $argType = $this->scope->withPartialArraysResolvingAsShapes(
                fn () => $methodArgs->get($index)->unwrapType($this->scope->config),
            );

            if (! ($argType instanceof ArrayType) || ! $argType->shape) {
                return null;
            }

            foreach (array_keys($argType->shape) as $attributeName) {
                $names[] = (string) $attributeName;
            }
        }

        return $names === [] ? null : $names;
    }


    private function getUnshapedRowResultType(string $methodName): ?Type
    {
        if ($methodName === 'get') {
            return new ArrayType(itemType: new ObjectType, className: Collection::class);
        }

        if (BuilderMethodClassifier::streamsRowsLazily($methodName)) {
            return new ArrayType(itemType: new ObjectType, className: LazyCollection::class);
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

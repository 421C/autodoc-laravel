<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\Helpers\MutatesModelReceiver;
use AutoDoc\Laravel\QueryBuilder\EagerLoad;
use AutoDoc\Laravel\QueryBuilder\RelationAggregate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhpParser\Node\Expr\Variable;

/**
 * Handles instance-side eager loading: `load()`, `loadMissing()` and the
 * `load{Count,Exists,Sum,Avg,Min,Max}` aggregates, on a model or on a
 * collection of models.
 */
class EloquentEagerLoad extends MethodCallExtension
{
    use MutatesModelReceiver;

    private const RELATION_METHODS = [
        'load',
        'loadMissing',
    ];

    private const AGGREGATE_METHODS = [
        'loadCount',
        'loadExists',
        'loadSum',
        'loadAvg',
        'loadMin',
        'loadMax',
    ];


    public function handleSideEffect(MethodCallContext $call): void
    {
        if (! $this->isEagerLoadMethod($call->methodName)) {
            return;
        }

        $receiverType = $call->getVarType();

        $modelType = $this->resolveModelObjectType($receiverType);

        if ($modelType !== null) {
            $this->mutateModelReceiver($call, $modelType, $this->resolveRelations($call, $modelType));

            return;
        }

        $var = $call->node->var;

        if (! ($var instanceof Variable) || ! is_string($var->name)) {
            return;
        }

        $loadedCollectionType = $this->resolveLoadedCollectionType($call, $receiverType);

        if ($loadedCollectionType !== null) {
            $call->setVarType($var->name, $loadedCollectionType);
        }
    }


    public function getReturnType(MethodCallContext $call): ?Type
    {
        if (! $this->isEagerLoadMethod($call->methodName)) {
            return null;
        }

        $receiverType = $call->getVarType();
        $modelType = $this->resolveModelObjectType($receiverType);

        if ($modelType === null) {
            return $this->resolveLoadedCollectionType($call, $receiverType);
        }

        $relations = $this->resolveRelations($call, $modelType);

        if ($relations === []) {
            return null;
        }

        $loadedModelType = clone $modelType;
        $loadedModelType->properties = array_merge($loadedModelType->properties, $relations);

        return $loadedModelType;
    }


    private function resolveLoadedCollectionType(MethodCallContext $call, Type $receiverType): ?ArrayType
    {
        if (! ($receiverType instanceof ArrayType)
            || ! $receiverType->className
            || ! is_a($receiverType->className, Collection::class, true)
        ) {
            return null;
        }

        $itemType = $receiverType->itemType === null
            ? null
            : $this->resolveModelObjectType($receiverType->itemType);

        if ($itemType === null) {
            return null;
        }

        $relations = $this->resolveRelations($call, $itemType);

        if ($relations === []) {
            return null;
        }

        $loadedItemType = clone $itemType;
        $loadedItemType->properties = array_merge($loadedItemType->properties, $relations);

        $loadedCollectionType = clone $receiverType;
        $loadedCollectionType->itemType = $loadedItemType;

        return $loadedCollectionType;
    }


    /**
     * @return array<string, Type>
     */
    private function resolveRelations(MethodCallContext $call, ObjectType $modelType): array
    {
        $modelClassName = $modelType->className;

        if ($modelClassName === null || ! is_subclass_of($modelClassName, Model::class, true)) {
            return [];
        }

        if (in_array($call->methodName, self::RELATION_METHODS, true)) {
            $eagerLoad = new EagerLoad($call->scope);
            $eagerLoad->addArguments($call->argTypes);

            return $eagerLoad->resolveRelationTypes($modelClassName);
        }

        return $this->resolveAggregateColumns($call, $modelClassName);
    }


    /**
     * @param class-string<Model> $modelClassName
     * @return array<string, Type>
     */
    private function resolveAggregateColumns(MethodCallContext $call, string $modelClassName): array
    {
        $aggregates = RelationAggregate::parse(
            methodName: $call->methodName,
            args: $call->argTypes,
            modelClassName: $modelClassName,
            scope: $call->scope,
        ) ?? [];

        $columns = [];

        foreach ($aggregates as $aggregate) {
            $columns[$aggregate->alias] = $aggregate->resolveType();
        }

        return $columns;
    }


    private function isEagerLoadMethod(string $methodName): bool
    {
        return in_array($methodName, self::RELATION_METHODS, true)
            || in_array($methodName, self::AGGREGATE_METHODS, true);
    }
}

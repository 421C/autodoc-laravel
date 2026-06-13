<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\PhpClass;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\DataTypes\UnresolvedParserNodeType;
use AutoDoc\Laravel\Helpers\DotNotationParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Throwable;


class QueryNavigator
{
    use DotNotationParser;

    public function __construct(
        private Scope $scope,
    ) {}

    /** @var ?class-string<Model> */
    private ?string $modelClassName = null;

    private ?ObjectType $modelType = null;

    private ?string $modelTableName = null;

    /**
     * @var array<int, array{
     *     name: string,
     *     args: ArgumentList,
     * }>
     */
    private array $builderMethods = [];

    /** @var array<array<string, Type>> */
    private array $columnSetVariants = [];

    /** @var array<string, Type> */
    private array $relationArguments = [];

    private bool $allMethodsAreBuilderMethods = true;

    private ?Type $collectionKeyType = null;


    public function getCollectionKeyType(): Type
    {
        return $this->collectionKeyType ?? new IntegerType;
    }


    public function getResultType(MethodCall|StaticCall $methodCall, string $methodName): ?Type
    {
        $rowType = $this->scope->withoutScalarTypeValueMerging(fn () => $this->getRowType($methodCall));

        if (! $rowType) {
            return null;
        }

        if ($methodName === 'count') {
            return new IntegerType(minimum: 0);
        }

        if ($methodName === 'get' || $methodName === 'pluck') {
            return new ArrayType(itemType: $rowType, className: Collection::class);
        }

        if (in_array($methodName, ['create', 'firstOrNew', 'firstOrCreate', 'updateOrCreate'])) {
            return $rowType;
        }

        if ($methodName === 'firstWhere') {
            return new UnionType([$rowType, new NullType]);
        }

        if ($methodName === 'paginate') {
            return $this->scope->withoutScalarTypeValueMerging(fn () => $this->getPaginatorType($rowType, $methodCall));
        }

        if (in_array($methodName, ['latest', 'oldest'])) {
            return new UnionType([$rowType, new NullType]);
        }

        if ($methodName === 'first') {
            return new UnionType([$rowType, new NullType]);
        }

        if ($methodName === 'firstOrFail') {
            return $rowType;
        }

        $methodArgs = ArgumentList::fromArgNodes($methodCall->args, $this->scope);

        if ($methodName === 'find' || $methodName === 'findOrFail') {
            $firstArg = $methodArgs->has(0) ? $methodArgs->get(0)->unwrapType($this->scope->config) : null;

            $multipleKeysPassed = $methodArgs->has(0)
                && ($firstArg instanceof ArrayType
                    || $firstArg instanceof ObjectType && $firstArg->typeToDisplay instanceof ArrayType);

            if ($multipleKeysPassed) {
                return new ArrayType(itemType: $rowType, className: Collection::class);
            }

            if ($methodName === 'find') {
                return new UnionType([$rowType, new NullType]);
            }

            return $rowType;
        }

        try {
            $phpClassMethod = $this->scope->getPhpClassInDeeperScope(\Illuminate\Database\Eloquent\Builder::class)->getMethod(
                name: $methodName,
                args: $methodArgs,
            );

            return $phpClassMethod->getReturnType()->unwrapType($this->scope->config);

        } catch (Throwable $exception) {
            $phpClassMethod = $this->scope->getPhpClassInDeeperScope(\Illuminate\Database\Query\Builder::class)->getMethod(
                name: $methodName,
                args: $methodArgs,
            );

            return $phpClassMethod->getReturnType()->unwrapType($this->scope->config);
        }
    }


    public function getRowType(Node\Expr $queryNode): ?Type
    {
        $this->extractBuilderMethodsAndModel($queryNode);

        if (! $this->modelClassName) {
            return null;
        }

        $this->checkBuilderMethods();

        if (! $this->modelClassName) {
            return null;
        }

        if (! $this->allMethodsAreBuilderMethods) {
            return null;
        }

        $modelPhpClass = $this->scope->getPhpClassInDeeperScope($this->modelClassName);

        $this->modelType = clone $modelPhpClass->resolveType();
        $this->modelTableName = app()->make($this->modelClassName)->getTable();

        foreach ($this->builderMethods as $builderMethod) {
            if ($builderMethod['name'] === 'select') {
                $this->columnSetVariants = [
                    $this->getColumnsFromArguments($builderMethod['args']),
                ];
            }

            if ($builderMethod['name'] === 'addSelect') {
                $this->handleAddSelect($builderMethod['args']);
            }

            if ($builderMethod['name'] === 'with') {
                $this->handleWith($builderMethod['args']);
            }

            if ($builderMethod['name'] === 'pluck') {
                return $this->handlePluck($builderMethod['args']);
            }

            if (in_array($builderMethod['name'], ['get', 'all'])) {
                if (count($builderMethod['args']) > 0) {
                    $this->columnSetVariants = [
                        $this->getColumnsFromArguments($builderMethod['args']),
                    ];
                }
            }
        }

        if (! $this->modelType) {
            return null;
        }

        if (! $this->columnSetVariants) {
            $this->columnSetVariants = [
                $this->modelType->properties,
            ];
        }

        $eagerLoadedRelations = $this->resolveEagerLoadedRelations();
        $rowType = new UnionType;

        foreach ($this->columnSetVariants as $columns) {
            $objectType = clone $this->modelType;

            if (isset($columns['*'])) {
                unset($columns['*']);

                $columns = array_merge($objectType->properties, $columns);
            }

            $objectType->properties = array_merge($columns, $eagerLoadedRelations);

            $rowType->types[] = $objectType;
        }

        return $rowType->unwrapType($this->scope->config);
    }

    private function getPaginatorType(Type $rowType, MethodCall|StaticCall $methodCall): Type
    {
        $args = ArgumentList::fromArgNodes($methodCall->args, $this->scope);
        $paginateMethod = $this->scope->getPhpClassInDeeperScope(Builder::class)->getMethod('paginate', $args);

        if ($this->scope->route) {
            $pageNameType = $paginateMethod->getArgumentType('pageName')->unwrapType($this->scope->config);
            $pageParamName = null;

            if ($pageNameType instanceof StringType && is_string($pageNameType->value)) {
                $pageParamName = $pageNameType->value;

            } else if ($pageNameType instanceof UnknownType) {
                $pageParamName = 'page';
            }

            if ($pageParamName) {
                $this->scope->route->requestQueryParams[$pageParamName] = new IntegerType;
            }
        }

        $columns = $this->getColumnsFromArgument($paginateMethod->getArgumentType('columns'));

        if ($columns) {
            if ($rowType instanceof ObjectType) {
                $eagerLoadedRelations = $this->resolveEagerLoadedRelations();

                if (isset($columns['*'])) {
                    unset($columns['*']);

                    $columns = array_merge($rowType->properties, $columns);
                }

                $rowType->properties = array_merge($columns, $eagerLoadedRelations);
            }
        }

        return (new Paginator(
            paginatorPhpClass: $this->scope->getPhpClassInDeeperScope(LengthAwarePaginator::class),
            entryClass: $this->modelClassName,
            entryType: $rowType,
        ))->resolveType();
    }

    private function handleAddSelect(ArgumentList $arguments): void
    {
        $columns = $this->getColumnsFromArguments($arguments);

        if ($this->columnSetVariants) {
            foreach ($this->columnSetVariants as $index => $columnSet) {
                $this->columnSetVariants[$index] = array_merge($columnSet, $columns);
            }

        } else {
            $this->columnSetVariants = [$columns];
        }
    }

    private function handleWith(ArgumentList $arguments): void
    {
        $argumentListArrayType = $this->scope->withPartialArraysResolvingAsShapes(function () use ($arguments) {
            if ($arguments->has(0)) {
                $firstArgType = $arguments->get(0)->unwrapType($this->scope->config);

                if ($firstArgType instanceof ArrayType) {
                    /**
                     * Example: ->with([
                     *     'planets:id,name',
                     *     'stations' => Closure,
                     *     'planets' => [
                     *         'stations' => Closure,
                     *         'moons',
                     *     ],
                     * ])
                     */

                    return $firstArgType;
                }
            }

            /**
             * Example: ->with('planets', 'moons')
             */
            $shape = [];

            for ($index = 0; $index < count($arguments); $index++) {
                $shape[] = $arguments->get($index)->unwrapType($this->scope->config);
            }

            return new ArrayType(shape: $shape);
        });

        $this->normalizeRelationArgumentArray($argumentListArrayType, $this->relationArguments);
    }

    /**
     * @param array<string, Type> &$normalizedShape
     */
    private function normalizeRelationArgumentArray(ArrayType $arrayType, array &$normalizedShape): void
    {
        $shape = $arrayType->shape;

        if (! $shape && $arrayType->itemType) {
            $shape = $arrayType->itemType instanceof UnionType
                ? $arrayType->itemType->types
                : [$arrayType->itemType];
        }

        foreach ($shape as $key => $valueType) {
            $valueType = $valueType->unwrapType($this->scope->config);

            if (is_string($key)) {
                $keyVariants = [$key];

                if ($valueType instanceof ArrayType) {
                    $relationArgumentShape = [];

                    $this->normalizeRelationArgumentArray($valueType, $relationArgumentShape);

                    $valueType = new ArrayType(shape: $relationArgumentShape);
                }

            } else {
                $keyVariants = [];

                if ($valueType instanceof StringType) {
                    $keyVariants = $valueType->getPossibleValues() ?? [];
                    $valueType = new UnknownType;
                }
            }

            foreach ($keyVariants as $dotNotationString) {
                $this->dotNotationToNestedArrayType($normalizedShape, $this->splitDotNotation($dotNotationString), $valueType);
            }
        }
    }

    private function handlePluck(ArgumentList $arguments): ?Type
    {
        if (! $arguments->has(0)) {
            return null;
        }

        $columnArg = $arguments->get(0);
        $keyArg = $arguments->has(1) ? $arguments->get(1) : null;

        $keyArgType = $keyArg?->unwrapType($this->scope->config);

        if ($keyArgType instanceof StringType) {
            $keyArgStrings = $keyArgType->getPossibleValues() ?? [];

            if (count($keyArgStrings) === 1) {
                [$propertyName, $propertyType] = $this->getColumnType($keyArgStrings[0]);

                $this->collectionKeyType = $propertyType;

            } else {
                $this->collectionKeyType = new UnionType(
                    array_filter(array_map(
                        fn ($keyArgString) => $this->getColumnType($keyArgString)[1],
                        $keyArgStrings,
                    ))
                );
            }
        }

        $columnArgType = $columnArg->unwrapType($this->scope->config);

        if ($columnArgType instanceof StringType) {
            $columnArgStrings = $columnArgType->getPossibleValues() ?? [];

            if (count($columnArgStrings) === 1) {
                return $this->getColumnType($columnArgStrings[0])[1] ?? new UnknownType;
            }

            return new UnionType(
                array_filter(array_map(
                    fn ($columnArgString) => $this->getColumnType($columnArgString)[1],
                    $columnArgStrings,
                ))
            );
        }

        return null;
    }


    /**
     * @param array<int, true> $visitedPositions
     */
    private function extractBuilderMethodsAndModel(Node\Expr $expr, array $visitedPositions = []): void
    {
        if ($expr instanceof MethodCall || $expr instanceof StaticCall) {
            if ($expr instanceof MethodCall) {
                $this->extractBuilderMethodsAndModel($expr->var, $visitedPositions);

            } else {
                if ($expr->class instanceof Node\Expr) {
                    $this->extractBuilderMethodsAndModel($expr->class, $visitedPositions);

                } else {
                    $className = $this->scope->getResolvedClassName($expr->class);

                    if (! $className) {
                        return;
                    }

                    if (! is_subclass_of($className, Model::class)) {
                        return;
                    }

                    $this->modelClassName = $className;
                }
            }

            $methodName = (string) $this->scope->getRawValueFromNode($expr->name);

            $this->builderMethods[] = [
                'name' => $methodName,
                'args' => ArgumentList::fromArgNodes($expr->args, $this->scope),
            ];

        } else if ($expr instanceof Node\Expr\Variable) {
            $position = $expr->getAttribute('startFilePos');

            if (is_int($position) && isset($visitedPositions[$position])) {
                return;
            }

            if (is_int($position)) {
                $visitedPositions[$position] = true;
            }

            if (is_string($expr->name)) {
                foreach ($this->scope->eventLog->getDirectAssignmentTypes($expr->name) as $type) {
                    if ($type instanceof UnresolvedParserNodeType && $type->node instanceof Node\Expr) {
                        $this->extractBuilderMethodsAndModel($type->node, $visitedPositions);

                        break;
                    }
                }
            }
        }
    }


    private function checkBuilderMethods(): void
    {
        $finisherMethods = array_fill_keys([
            'all', 'get', 'find', 'findor', 'findorfail', 'first', 'firstorfail', 'firstornew', 'firstorcreate',
            'latest', 'oldest', 'pluck', 'paginate', 'simplepaginate', 'cursorpaginate', 'cursor',
            'lazy', 'lazybyid', 'lazybyiddesc',
            'chunk', 'chunkmap', 'chunkbyid', 'chunkbyiddesc', 'orderedchunkbyid', 'each', 'eachbyid',
            'sum', 'avg', 'min', 'max', 'average', 'aggregate', 'numericaggregate',
            'count', 'exists', 'doesntexist', 'existsor', 'doesntexistor',
            'insert', 'insertorignore', 'insertgetid', 'insertusing', 'insertorignoreusing',
            'update', 'updatefrom', 'updateorinsert', 'upsert', 'delete', 'forcedelete',
            'increment', 'incrementeach', 'decrement', 'decrementeach',
            'value', 'rawvalue', 'solevalue', 'sole', 'tosql', 'torawsql', 'implode',
            'create', 'createquietly', 'forcecreate', 'forcecreatequietly', 'touch',
        ], true);

        $methodCount = count($this->builderMethods);

        for ($i = 0; $i < $methodCount - 1; $i++) {
            $methodName = strtolower($this->builderMethods[$i]['name']);

            if (isset($finisherMethods[$methodName])) {
                $this->allMethodsAreBuilderMethods = false;

                return;
            }
        }

        if (config('autodoc.laravel.abandon_query_builder_parsing_on_unknown_methods') ?? false) {
            static $builderClassMethods = null;

            if ($builderClassMethods === null) {
                $builderClassMethods = array_fill_keys(array_merge(
                    array_map(strtolower(...), get_class_methods(\Illuminate\Database\Eloquent\Builder::class)),
                    array_map(strtolower(...), get_class_methods(\Illuminate\Database\Query\Builder::class)),
                ), true);
            }

            foreach ($this->builderMethods as $method) {
                $methodName = strtolower($method['name']);

                if (! isset($builderClassMethods[$methodName])) {
                    $this->allMethodsAreBuilderMethods = false;

                    return;
                }
            }
        }
    }


    /**
     * @return array<string, Type>
     */
    private function getColumnsFromArguments(ArgumentList $args): array
    {
        $columns = [];
        $columnTypes = [];
        $firstArgIsArray = false;

        if ($args->has(0)) {
            $firstArgType = $args->get(0)->unwrapType($this->scope->config);

            if ($firstArgType instanceof ArrayType) {
                /**
                 * Example: ->select([
                 *     'id',
                 *     'name',
                 * ])
                 */
                $firstArgIsArray = true;
                $arrayItemType = $firstArgType->convertShapeToTypePair($this->scope->config)->itemType;

                if ($arrayItemType instanceof UnionType) {
                    // array<ColumnA | ColumnB> -> [ColumnA, ColumnB]
                    $columnTypes = $arrayItemType->types;

                } else if ($arrayItemType) {
                    // array<ColumnA> -> [ColumnA]
                    $columnTypes = [$arrayItemType];
                }
            }
        }

        if (! $firstArgIsArray) {
            /**
             * Example: ->select('id', 'name')
             */
            for ($index = 0; $index < count($args); $index++) {
                if ($index === 0 && isset($firstArgType)) {
                    $argType = $firstArgType;

                } else {
                    $argType = $args->get($index)->unwrapType($this->scope->config);
                }

                $columnTypes[] = $argType;
            }
        }

        foreach ($columnTypes as $columnType) {
            if ($columnType instanceof StringType) {
                $columnStrings = $columnType->getPossibleValues() ?? [];

                foreach ($columnStrings as $columnString) {
                    [$propertyName, $propertyType] = $this->getColumnType($columnString);

                    if ($propertyName !== null) {
                        $columns[$propertyName] = $propertyType ?? new UnknownType;
                    }
                }
            }
        }

        return $columns;
    }


    /**
     * @return array<string, Type>
     */
    private function getColumnsFromArgument(Type $columnsArgType): array
    {
        $columnsArgType = $columnsArgType->unwrapType($this->scope->config);

        $columnTypes = [];

        if ($columnsArgType instanceof ArrayType) {
            $arrayItemType = $columnsArgType->convertShapeToTypePair($this->scope->config)->itemType;

            if ($arrayItemType instanceof UnionType) {
                // array<ColumnA | ColumnB> -> [ColumnA, ColumnB]
                $columnTypes = $arrayItemType->types;

            } else if ($arrayItemType) {
                // array<ColumnA> -> [ColumnA]
                $columnTypes = [$arrayItemType];
            }

        } else {
            $columnTypes[] = $columnsArgType;
        }

        $columns = [];

        foreach ($columnTypes as $columnType) {
            if ($columnType instanceof StringType) {
                $columnStrings = $columnType->getPossibleValues() ?? [];

                foreach ($columnStrings as $columnString) {
                    [$propertyName, $propertyType] = $this->getColumnType($columnString);

                    if ($propertyName !== null) {
                        $columns[$propertyName] = $propertyType ?? new UnknownType;
                    }
                }
            }
        }

        return $columns;
    }


    /**
     * @return array{string|null, Type|null}
     */
    private function getColumnType(string $column): array
    {
        $prefix = null;
        $alias = null;

        if (str_contains($column, '.')) {
            [$prefix, $column] = explode('.', $column);
        }

        if (str_contains($column, ' as ')) {
            [$column, $alias] = explode(' as ', $column);
        }

        $propertyName = trim($alias ?? $column);
        $propertyType = new UnknownType;

        if (! $propertyName) {
            return [null, null];
        }

        if (! $prefix || $prefix === $this->modelTableName) {
            if (isset($this->modelType->properties[$column])) {
                $propertyType = clone $this->modelType->properties[$column];
            }
        }

        return [$propertyName, $propertyType];
    }


    /**
     * @return array<string, Type>
     */
    private function resolveEagerLoadedRelations(): array
    {
        if (! $this->modelClassName) {
            return [];
        }

        if (! $this->relationArguments) {
            return [];
        }

        $modelPhpClass = $this->scope->getPhpClassInDeeperScope($this->modelClassName);
        $relations = [];

        foreach ($this->relationArguments as $key => $relationArgumentType) {
            $relation = $this->makeRelationObject($key, $relationArgumentType, $modelPhpClass);

            if (isset($relations[$relation->exportedName])) {
                $relations[$relation->exportedName]->columns = array_merge($relations[$relation->exportedName]->columns, $relation->columns);
                $relations[$relation->exportedName]->relations = array_merge($relations[$relation->exportedName]->relations, $relation->relations);

            } else {
                $relations[$relation->exportedName] = $relation;
            }
        }

        $relationTypes = [];

        foreach ($relations as $name => $relation) {
            $relationTypes[$name] = $relation->resolveType() ?? new UnknownType;
        }

        return $relationTypes;
    }


    /**
     * @param PhpClass<Model> $modelPhpClass
     */
    private function makeRelationObject(string $key, Type $relationArgumentType, PhpClass $modelPhpClass): Relation
    {
        $parts = explode(':', $key, 2);

        $name = $parts[0];
        $columns = isset($parts[1]) ? explode(',', $parts[1]) : [];

        $relationArgumentType = $this->scope->withPartialArraysResolvingAsShapes(
            fn () => $relationArgumentType->unwrapType($this->scope->config)
        );

        $relation = new Relation(
            modelPhpClass: $modelPhpClass,
            name: $name,
            columns: $columns,
            relations: [],
        );

        if ($relationArgumentType instanceof ArrayType) {
            $relatedModelClassName = $relation->getRelatedModelClassName();

            if ($relatedModelClassName) {
                $relatedModelPhpClass = $modelPhpClass->scope->getPhpClassInDeeperScope($relatedModelClassName);

                if ($relationArgumentType->shape) {
                    foreach ($relationArgumentType->shape as $subRelationKey => $valueType) {
                        $subRelation = $this->makeRelationObject((string) $subRelationKey, $valueType, $relatedModelPhpClass);

                        $relation->relations[$subRelation->exportedName] = $subRelation;
                    }

                } else if ($relationArgumentType->itemType instanceof StringType) {
                    foreach ($relationArgumentType->itemType->getPossibleValues() ?? [] as $subRelationKey) {
                        $subRelation = $this->makeRelationObject($subRelationKey, new UnknownType, $relatedModelPhpClass);

                        $relation->relations[$subRelation->exportedName] = $subRelation;
                    }
                }
            }
        }

        return $relation;
    }
}

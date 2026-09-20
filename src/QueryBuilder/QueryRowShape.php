<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\PhpClass;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Laravel\Helpers\DotNotationParser;
use AutoDoc\Laravel\Helpers\ModelResolver;
use Illuminate\Database\Eloquent\Model;

final class QueryRowShape
{
    use DotNotationParser;

    public function __construct(
        private Scope $scope,
        private QueryChain $chain,
    ) {}

    private ?ObjectType $baseRowType = null;

    private bool $sourceIsResolved = false;

    private ?string $sourceTableName = null;

    private ?string $sourceTableAlias = null;

    /** @var ?array<string, Type> */
    private ?array $selectedColumns = null;

    /** @var array<string, Type> */
    private array $relationArguments = [];

    private ?Type $pluckedKeyType = null;


    public function resolveRowType(): ?Type
    {
        $baseRowType = $this->getBaseRowType();

        if (! $baseRowType) {
            return null;
        }

        foreach ($this->chain->methods as $method) {
            if ($method->name === 'select') {
                $this->selectedColumns = $this->getColumnsFromArguments($method->args);
            }

            if ($method->name === 'addSelect') {
                $this->selectedColumns = array_merge($this->selectedColumns ?? [], $this->getColumnsFromArguments($method->args));
            }

            if ($method->name === 'with') {
                $this->addEagerLoadedRelationArguments($method->args);
            }

            if ($method->name === 'pluck') {
                return $this->resolvePluckedColumnType($method->args);
            }

            if (($method->name === 'get' || $method->name === 'all') && count($method->args) > 0) {
                $this->selectColumnsUnlessAlreadySelected($this->getColumnsFromArguments($method->args));
            }
        }

        return $this->applyColumns(
            clone $baseRowType,
            $this->selectedColumns ?? $baseRowType->properties,
            $this->resolveEagerLoadedRelations(),
        );
    }


    public function selectsExplicitColumns(): bool
    {
        return $this->selectedColumns !== null;
    }


    public function getPluckedKeyType(): ?Type
    {
        return $this->pluckedKeyType;
    }


    /**
     * @param array<string, Type> $columns
     */
    public function selectColumnsUnlessAlreadySelected(array $columns): void
    {
        $this->selectedColumns ??= $columns;
    }


    /**
     * @param array<string, Type> $columns
     * @param array<string, Type> $eagerLoadedRelations
     */
    public function applyColumns(ObjectType $objectType, array $columns, array $eagerLoadedRelations): ObjectType
    {
        if (isset($columns['*'])) {
            unset($columns['*']);

            $columns = array_merge($objectType->properties, $columns);
        }

        $objectType->properties = array_merge($columns, $eagerLoadedRelations);

        return $objectType;
    }


    /**
     * @return array<string, Type>
     */
    public function getColumnsFromArgument(Type $columnsArgType): array
    {
        return $this->getColumnsFromTypes($this->getColumnTypes($columnsArgType));
    }


    /**
     * @return list<Type>
     */
    public function resolveColumnTypesNamedByArgument(ArgumentList $args): array
    {
        if (! $args->has(0)) {
            return [];
        }

        $columnArgType = $args->get(0)->unwrapType($this->scope->config);

        if (! ($columnArgType instanceof StringType)) {
            return [];
        }

        $columnTypes = [];

        foreach ($columnArgType->getPossibleValues() ?? [] as $columnName) {
            $column = $this->selectColumn($columnName);

            if ($column) {
                $columnTypes[] = $column[1];
            }
        }

        return $columnTypes;
    }


    /**
     * @return array<string, Type>
     */
    public function resolveEagerLoadedRelations(): array
    {
        $modelClassName = $this->chain->modelClassName;

        if (! $modelClassName || ! $this->relationArguments) {
            return [];
        }

        $modelPhpClass = $this->scope->getPhpClassInDeeperScope($modelClassName);
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


    private function getBaseRowType(): ?ObjectType
    {
        if ($this->sourceIsResolved) {
            return $this->baseRowType;
        }

        $this->sourceIsResolved = true;

        $modelClassName = $this->chain->modelClassName;

        if ($modelClassName) {
            $this->sourceTableName = ModelResolver::resolve($modelClassName)?->getTable();

            return $this->baseRowType = clone $this->scope->getPhpClassInDeeperScope($modelClassName)->resolveType();
        }

        return $this->baseRowType = $this->resolveTableRowType();
    }


    private function resolveTableRowType(): ?ObjectType
    {
        $connectionName = null;
        $tableName = null;

        foreach ($this->chain->methods as $method) {
            if (self::widensOrHidesTheRow($method->name)) {
                return null;
            }

            if ($method->name === 'connection') {
                $connectionName = $this->getStringArgument($method->args);
            }

            if ($method->name === 'table' || $method->name === 'from') {
                $tableName = $this->getStringArgument($method->args);
            }
        }

        if ($tableName === null) {
            return null;
        }

        [$tableName, $alias] = $this->splitAlias($tableName);

        $this->sourceTableName = $tableName;
        $this->sourceTableAlias = $alias;

        return TableSchema::resolveRowType($tableName, $connectionName);
    }


    private static function widensOrHidesTheRow(string $methodName): bool
    {
        $methodName = strtolower($methodName);

        return str_contains($methodName, 'join')
            || str_starts_with($methodName, 'union')
            || $methodName === 'fromsub'
            || $methodName === 'fromraw';
    }


    private function getStringArgument(ArgumentList $args): ?string
    {
        if (! $args->has(0)) {
            return null;
        }

        $argType = $args->get(0)->unwrapType($this->scope->config);

        if (! ($argType instanceof StringType)) {
            return null;
        }

        $values = $argType->getPossibleValues() ?? [];

        return count($values) === 1 ? $values[0] : null;
    }


    private function addEagerLoadedRelationArguments(ArgumentList $arguments): void
    {
        $argumentListArrayType = $this->scope->withPartialArraysResolvingAsShapes(function () use ($arguments) {
            if ($arguments->has(0)) {
                $firstArgType = $arguments->get(0)->unwrapType($this->scope->config);

                if ($firstArgType instanceof ArrayType) {
                    return $firstArgType;
                }
            }

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


    private function resolvePluckedColumnType(ArgumentList $arguments): ?Type
    {
        if (! $arguments->has(0)) {
            return null;
        }

        $keyArgType = $arguments->has(1) ? $arguments->get(1)->unwrapType($this->scope->config) : null;

        if ($keyArgType instanceof StringType) {
            $this->pluckedKeyType = $this->getTypeOfColumnsNamedBy($keyArgType);
        }

        $columnArgType = $arguments->get(0)->unwrapType($this->scope->config);

        if (! ($columnArgType instanceof StringType)) {
            return null;
        }

        return $this->getTypeOfColumnsNamedBy($columnArgType) ?? new UnknownType;
    }


    private function getTypeOfColumnsNamedBy(StringType $columnNameType): ?Type
    {
        $columnTypes = [];

        foreach ($columnNameType->getPossibleValues() ?? [] as $columnName) {
            $column = $this->selectColumn($columnName);

            if ($column) {
                $columnTypes[] = $column[1];
            }
        }

        return match (count($columnTypes)) {
            0 => null,
            1 => $columnTypes[0],
            default => new UnionType($columnTypes),
        };
    }


    /**
     * @return array<string, Type>
     */
    private function getColumnsFromArguments(ArgumentList $args): array
    {
        if (! $args->has(0)) {
            return [];
        }

        $firstArgType = $args->get(0)->unwrapType($this->scope->config);

        if ($firstArgType instanceof ArrayType) {
            return $this->getColumnsFromTypes($this->getColumnTypes($firstArgType));
        }

        $columnTypes = [$firstArgType];

        for ($index = 1; $index < count($args); $index++) {
            $columnTypes[] = $args->get($index)->unwrapType($this->scope->config);
        }

        return $this->getColumnsFromTypes($columnTypes);
    }


    /**
     * @return list<Type>
     */
    private function getColumnTypes(Type $type): array
    {
        $type = $type->unwrapType($this->scope->config);

        if (! $type instanceof ArrayType) {
            return [$type];
        }

        $itemType = $type->convertShapeToTypePair($this->scope->config)->itemType;

        if ($itemType instanceof UnionType) {
            return array_values($itemType->types);
        }

        return $itemType ? [$itemType] : [];
    }


    /**
     * @param list<Type> $columnTypes
     * @return array<string, Type>
     */
    private function getColumnsFromTypes(array $columnTypes): array
    {
        $columns = [];

        foreach ($columnTypes as $columnType) {
            if ($columnType instanceof StringType) {
                foreach ($columnType->getPossibleValues() ?? [] as $columnString) {
                    $column = $this->selectColumn($columnString);

                    if ($column) {
                        $columns[$column[0]] = $column[1];
                    }
                }
            }
        }

        return $columns;
    }


    /**
     * @return ?array{string, Type}
     */
    private function selectColumn(string $columnExpression): ?array
    {
        [$column, $alias] = $this->splitAlias($columnExpression);

        if (str_contains($column, '->')) {
            $name = $alias ?? $this->jsonPathLeaf($column);

            return $name === '' ? null : [$name, new UnknownType];
        }

        [$table, $column] = $this->splitTablePrefix($column);
        $name = $alias ?? $column;

        if ($name === '') {
            return null;
        }

        return [$name, $this->getColumnType($table, $column)];
    }


    private function getColumnType(?string $table, string $column): Type
    {
        if ($table !== null && $table !== $this->sourceTableName && $table !== $this->sourceTableAlias) {
            return new UnknownType;
        }

        $columnType = $this->getBaseRowType()?->properties[$column] ?? null;

        return $columnType ? clone $columnType : new UnknownType;
    }


    /**
     * @return array{string, ?string}
     */
    private function splitAlias(string $columnExpression): array
    {
        $parts = preg_split('/\s+as\s+/i', $columnExpression, 2);

        if ($parts && count($parts) === 2) {
            return [trim($parts[0]), trim($parts[1])];
        }

        return [trim($columnExpression), null];
    }


    /**
     * @return array{?string, string}
     */
    private function splitTablePrefix(string $column): array
    {
        $segments = explode('.', $column);
        $name = (string) array_pop($segments);

        return [array_pop($segments), $name];
    }


    private function jsonPathLeaf(string $column): string
    {
        $segments = explode('->', $column);

        return trim((string) end($segments));
    }


    /**
     * @param PhpClass<Model> $modelPhpClass
     */
    private function makeRelationObject(string $key, Type $relationArgumentType, PhpClass $modelPhpClass): Relation
    {
        $parts = explode(':', $key, 2);

        $relation = new Relation(
            modelPhpClass: $modelPhpClass,
            name: $parts[0],
            columns: isset($parts[1]) ? explode(',', $parts[1]) : [],
            relations: [],
        );

        $relationArgumentType = $this->scope->withPartialArraysResolvingAsShapes(
            fn () => $relationArgumentType->unwrapType($this->scope->config)
        );

        if (! ($relationArgumentType instanceof ArrayType)) {
            return $relation;
        }

        $relatedModelClassName = $relation->getRelatedModelClassName();

        if (! $relatedModelClassName) {
            return $relation;
        }

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

        return $relation;
    }
}

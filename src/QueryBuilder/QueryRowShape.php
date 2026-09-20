<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Laravel\Helpers\ParsesSqlAliases;

final class QueryRowShape
{
    use ParsesSqlAliases;

    public function __construct(
        private Scope $scope,
        private QueryChain $chain,
    ) {
        $this->eagerLoad = new EagerLoad($scope);
    }

    private readonly EagerLoad $eagerLoad;

    private ?FromClause $fromClause = null;

    private ?ObjectType $baseRowType = null;

    private bool $sourceIsResolved = false;

    /** @var ?array<string, Type> */
    private ?array $selectedColumns = null;

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
                $this->addSelectedColumns($this->getColumnsFromArguments($method->args));
            }

            if ($method->name === 'with') {
                $this->eagerLoad->addArguments($method->args);
            }

            $aggregateColumns = $this->getRelationAggregateColumns($method);

            if ($aggregateColumns !== null) {
                $this->selectAllColumnsUnlessAlreadySelected();
                $this->addSelectedColumns($aggregateColumns);
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

        return $modelClassName ? $this->eagerLoad->resolveRelationTypes($modelClassName) : [];
    }


    private function selectAllColumnsUnlessAlreadySelected(): void
    {
        $this->selectColumnsUnlessAlreadySelected(['*' => new UnknownType]);
    }


    /**
     * @param array<string, Type> $columns
     */
    private function addSelectedColumns(array $columns): void
    {
        $this->selectedColumns = array_merge($this->selectedColumns ?? [], $columns);
    }


    /**
     * @return ?array<string, Type>
     */
    private function getRelationAggregateColumns(QueryChainMethod $method): ?array
    {
        $modelClassName = $this->chain->modelClassName;

        if ($modelClassName === null || $this->chain->isRawDatabaseQuery) {
            return null;
        }

        $aggregates = RelationAggregate::parse(
            methodName: $method->name,
            args: $method->args,
            modelClassName: $modelClassName,
            scope: $this->scope,
        );

        if ($aggregates === null) {
            return null;
        }

        $columns = [];

        foreach ($aggregates as $aggregate) {
            $columns[$aggregate->alias] = $aggregate->resolveType();
        }

        return $columns;
    }


    private function getBaseRowType(): ?ObjectType
    {
        if ($this->sourceIsResolved) {
            return $this->baseRowType;
        }

        $this->sourceIsResolved = true;

        return $this->baseRowType = $this->fromClause()->resolveRowType();
    }


    private function fromClause(): FromClause
    {
        return $this->fromClause ??= new FromClause(
            scope: $this->scope,
            chain: $this->chain,
        );
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
        $columnType = $this->fromClause()->columnType($table, $column);

        if (! $columnType && $table === null) {
            $columnType = $this->selectedColumns[$column] ?? null;
        }

        return $columnType ? clone $columnType : new UnknownType;
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
}

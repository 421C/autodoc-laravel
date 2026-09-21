<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\NumberType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Laravel\Helpers\ModelResolver;
use AutoDoc\Laravel\Helpers\ParsesSqlExpressions;

final class QueryRowShape
{
    use ParsesSqlExpressions;

    public function __construct(
        private Scope $scope,
        private QueryChain $chain,
    ) {
        $this->eagerLoad = new EagerLoad($scope);
        $this->conditionalEagerLoad = new EagerLoad($scope);
    }

    private readonly EagerLoad $eagerLoad;

    private readonly EagerLoad $conditionalEagerLoad;

    private bool $applyingConditionalMethod = false;

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

        $this->applyModelDefaults($baseRowType);

        foreach ($this->chain->methods as $method) {
            $this->applyingConditionalMethod = $method->runsConditionally;

            if ($method->name === 'select' && ! $method->runsConditionally) {
                $this->selectedColumns = $method->args->has(0)
                    ? $this->getColumnsFromArguments($method->args)
                    : self::starSelection();
            }

            $addedColumns = match ($method->name) {
                'addSelect' => $this->getColumnsFromArguments($method->args),
                'selectRaw' => $this->getColumnsFromRawArguments($method->args),
                'selectSub' => $this->getSubQueryColumns($method->args),
                default => null,
            };

            if ($addedColumns !== null) {
                if ($method->runsConditionally && ! $this->selectsExplicitColumns()) {
                    $this->selectedColumns = self::asOptionalProperties($baseRowType->properties);
                }

                $this->addSelectedColumns($addedColumns);
            }

            if ($method->name === 'with') {
                $this->eagerLoadFor($method)->addArguments($method->args);
            }

            if ($method->name === 'withOnly' && ! $method->runsConditionally) {
                $this->conditionalEagerLoad->removeAllArguments();
                $this->eagerLoad->replaceArguments($method->args);
            }

            if ($method->name === 'withWhereHas') {
                $this->eagerLoadFor($method)->addRelationArgument($method->args);
            }

            if ($method->name === 'without' && ! $method->runsConditionally) {
                $this->eagerLoad->removeArguments($method->args);
                $this->conditionalEagerLoad->removeArguments($method->args);
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
        $expandedColumns = [];

        foreach ($columns as $name => $columnType) {
            if (self::isStarSelection($name)) {
                $expandedColumns = array_merge($expandedColumns, $this->expandStarSelection($name, $objectType));

                continue;
            }

            $expandedColumns[$name] = $columnType;
        }

        $objectType->properties = array_merge($expandedColumns, $eagerLoadedRelations);

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

        if (! $modelClassName) {
            return [];
        }

        $conditional = self::asOptionalProperties(
            $this->conditionalEagerLoad->resolveRelationTypes($modelClassName),
        );

        return array_merge($conditional, $this->eagerLoad->resolveRelationTypes($modelClassName));
    }


    /**
     * Laravel seeds `$with` and `$withCount` when the builder is created, so
     * they apply before anything the chain does. A later `select()` replaces
     * the columns and drops the counts, while the relations survive it.
     */
    private function applyModelDefaults(ObjectType $baseRowType): void
    {
        $modelClassName = $this->chain->modelClassName;

        if ($modelClassName === null || $this->chain->isRawDatabaseQuery) {
            return;
        }

        foreach (array_keys(EagerLoad::defaultRelationTypes($this->scope, $modelClassName)) as $defaultRelationName) {
            unset($baseRowType->properties[$defaultRelationName]);
        }

        $this->eagerLoad->addRelationNames(ModelResolver::defaultEagerLoads($modelClassName));

        $countColumns = RelationAggregate::defaultCountColumns(
            $this->scope->getPhpClassInDeeperScope($modelClassName),
        );

        if ($countColumns) {
            $this->selectAllColumnsUnlessAlreadySelected();
            $this->addSelectedColumns($countColumns);
        }
    }


    private function selectAllColumnsUnlessAlreadySelected(): void
    {
        $this->selectColumnsUnlessAlreadySelected(self::starSelection());
    }


    /**
     * @return array<string, Type>
     */
    private static function starSelection(): array
    {
        return ['*' => new UnknownType];
    }


    private static function isStarSelection(string $name): bool
    {
        return $name === '*' || str_ends_with($name, '.*');
    }


    /**
     * @return array<string, Type>
     */
    private function expandStarSelection(string $name, ObjectType $rowType): array
    {
        if ($name === '*') {
            return $rowType->properties;
        }

        [$table] = self::splitTablePrefix($name);
        $tableRowType = $this->fromClause()->tableRowType($table);

        return $tableRowType ? $tableRowType->properties : [];
    }


    /**
     * @param array<string, Type> $columns
     */
    private function addSelectedColumns(array $columns): void
    {
        if ($this->applyingConditionalMethod) {
            $columns = self::asOptionalProperties($columns);
        }

        $this->selectedColumns = array_merge($this->selectedColumns ?? [], $columns);
    }


    /**
     * @param array<string, Type> $properties
     * @return array<string, Type>
     */
    private static function asOptionalProperties(array $properties): array
    {
        return array_map(fn (Type $type) => (clone $type)->setRequired(false), $properties);
    }


    private function eagerLoadFor(QueryChainMethod $method): EagerLoad
    {
        return $method->runsConditionally ? $this->conditionalEagerLoad : $this->eagerLoad;
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

                continue;
            }

            $rawSelectList = RawSelectExpression::readExpressionArgument($columnType, $this->scope->config);

            if ($rawSelectList !== null) {
                $columns = array_merge($columns, $this->getColumnsFromRawSelectList($rawSelectList));
            }
        }

        return $columns;
    }


    /**
     * @return array<string, Type>
     */
    private function getColumnsFromRawArguments(ArgumentList $args): array
    {
        $expressionIndex = $args->indexForParameter('expression', 0);

        if ($expressionIndex === null) {
            return [];
        }

        $expressionArgType = $args->get($expressionIndex)->unwrapType($this->scope->config);

        if (! ($expressionArgType instanceof StringType)) {
            return [];
        }

        $columns = [];

        foreach ($expressionArgType->getPossibleValues() ?? [] as $selectList) {
            $columns = array_merge($columns, $this->getColumnsFromRawSelectList($selectList));
        }

        return $columns;
    }


    /**
     * @return array<string, Type>
     */
    private function getColumnsFromRawSelectList(string $selectList): array
    {
        $columns = [];

        foreach (RawSelectExpression::parseList($selectList) as $rawColumn) {
            $column = $this->selectRawColumn($rawColumn);

            if ($column) {
                $columns[$column[0]] = $column[1];
            }
        }

        return $columns;
    }


    /**
     * An expression the driver would name differently on every database is
     * left out rather than guessed at.
     *
     * @return ?array{string, Type}
     */
    private function selectRawColumn(RawSelectExpression $rawColumn): ?array
    {
        if ($rawColumn->functionName !== null) {
            return $rawColumn->alias === null
                ? null
                : [$rawColumn->alias, $this->getRawFunctionType($rawColumn)->setRequired(true)];
        }

        if ($rawColumn->alias === null && ! $rawColumn->isPlainColumnReference()) {
            return null;
        }

        return $this->selectAliasedColumn($rawColumn->expression, $rawColumn->alias);
    }


    private function getRawFunctionType(RawSelectExpression $rawColumn): Type
    {
        return match ($rawColumn->functionName) {
            'count' => new IntegerType(minimum: 0),
            'sum', 'avg' => $this->orNull(new NumberType),
            'min', 'max' => $this->orNull($this->getAggregatedColumnType($rawColumn) ?? new NumberType),
            default => new UnknownType,
        };
    }


    private function getAggregatedColumnType(RawSelectExpression $rawColumn): ?Type
    {
        $argument = $rawColumn->functionArgument;

        if ($argument === null || ! RawSelectExpression::isPlainColumn($argument)) {
            return null;
        }

        [$table, $column] = $this->splitTablePrefix($argument);
        $columnType = $this->fromClause()->columnType($table, $column);

        return $columnType ? clone $columnType : null;
    }


    private function orNull(Type $type): Type
    {
        return (new UnionType([$type, new NullType]))->unwrapType($this->scope->config);
    }


    /**
     * @return array<string, Type>
     */
    private function getSubQueryColumns(ArgumentList $args): array
    {
        $aliasIndex = $args->indexForParameter('as', 1);

        if ($aliasIndex === null) {
            return [];
        }

        $aliasArgType = $args->get($aliasIndex)->unwrapType($this->scope->config);

        if (! ($aliasArgType instanceof StringType)) {
            return [];
        }

        $columns = [];

        foreach ($aliasArgType->getPossibleValues() ?? [] as $alias) {
            $columns[$alias] = (new UnknownType)->setRequired(true);
        }

        return $columns;
    }


    /**
     * @return ?array{string, Type}
     */
    private function selectColumn(string $columnExpression): ?array
    {
        [$column, $alias] = self::splitAlias($columnExpression);

        return $this->selectAliasedColumn($column, $alias);
    }


    /**
     * @return ?array{string, Type}
     */
    private function selectAliasedColumn(string $column, ?string $alias): ?array
    {
        if (str_contains($column, '->')) {
            $name = $alias ?? $this->jsonPathLeaf($column);

            return $name === '' ? null : [$name, (new UnknownType)->setRequired(true)];
        }

        [$table, $column] = $this->splitTablePrefix($column);

        if ($column === '*' && $alias === null) {
            return [$table === null ? '*' : $table . '.*', new UnknownType];
        }

        $name = $alias ?? $column;

        if ($name === '') {
            return null;
        }

        return [$name, $this->getColumnType($table, $column)->setRequired(true)];
    }


    private function getColumnType(?string $table, string $column): Type
    {
        $columnType = $this->fromClause()->columnType($table, $column);

        if (! $columnType && $table === null) {
            $columnType = $this->selectedColumns[$column] ?? null;
        }

        return $columnType ? clone $columnType : new UnknownType;
    }


    private function jsonPathLeaf(string $column): string
    {
        $segments = explode('->', $column);

        return trim((string) end($segments));
    }
}

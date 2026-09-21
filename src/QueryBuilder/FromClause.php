<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Laravel\Helpers\ModelResolver;
use AutoDoc\Laravel\Helpers\ParsesSqlExpressions;
use Illuminate\Database\Eloquent\Model;

final class FromClause
{
    use ParsesSqlExpressions;

    public function __construct(
        private Scope $scope,
        private QueryChain $chain,
    ) {}

    private const JOIN_METHODS = [
        'join',
        'joinWhere',
        'crossJoin',
        'leftJoin',
        'leftJoinWhere',
        'rightJoin',
        'rightJoinWhere',
        'straightJoin',
        'straightJoinWhere',
    ];

    private const ROW_HIDING_METHODS = [
        'joinSub',
        'joinLateral',
        'leftJoinSub',
        'leftJoinLateral',
        'rightJoinSub',
        'crossJoinSub',
        'straightJoinSub',
        'union',
        'unionAll',
        'fromSub',
        'fromRaw',
    ];

    /**
     * @var list<QueryTable>
     */
    private array $tables = [];

    private ?string $connectionName = null;

    private bool $rowIsIndeterminate = false;

    private bool $isResolved = false;

    private ?ObjectType $rowType = null;

    private bool $rowTypeIsResolved = false;


    public function resolveRowType(): ?ObjectType
    {
        if ($this->rowTypeIsResolved) {
            return $this->rowType;
        }

        $this->rowTypeIsResolved = true;
        $this->resolve();

        $baseTable = $this->tables[0] ?? null;
        $baseRowType = $baseTable?->rowType();

        if (! $baseRowType) {
            return null;
        }

        $rowType = clone $baseRowType;

        foreach (array_slice($this->tables, 1) as $joinedTable) {
            $joinedRowType = $joinedTable->rowType();

            if ($joinedRowType) {
                $rowType->properties = array_merge($rowType->properties, $joinedRowType->properties);
            }
        }

        return $this->rowType = $rowType;
    }


    public function tableRowType(?string $tablePrefix): ?ObjectType
    {
        if ($tablePrefix === null) {
            return $this->resolveRowType();
        }

        $this->resolve();

        foreach ($this->tables as $table) {
            if ($table->matchesPrefix($tablePrefix)) {
                return $table->rowType();
            }
        }

        return null;
    }


    public function columnType(?string $tablePrefix, string $columnName): ?Type
    {
        $rowType = $this->tableRowType($tablePrefix);

        return $rowType?->properties[$columnName] ?? $rowType?->hiddenProperties[$columnName] ?? null;
    }


    private function resolve(): void
    {
        if ($this->isResolved) {
            return;
        }

        $this->isResolved = true;
        $this->connectionName = $this->getStringArgument($this->findLastMethod('connection', 'on'))
            ?? $this->model()?->getConnectionName();

        $baseTable = $this->createBaseTable();

        if (! $baseTable) {
            return;
        }

        $this->tables = [$baseTable];

        foreach ($this->chain->methods as $method) {
            if (in_array($method->name, self::JOIN_METHODS, strict: true)) {
                $this->addJoinedTable($method);

            } else if (self::hidesTheRow($method->name)) {
                $this->rowIsIndeterminate = true;
            }
        }

        if ($this->rowIsIndeterminate) {
            $this->tables = $this->chain->modelClassName === null ? [] : [$baseTable];
        }
    }


    private function createBaseTable(): ?QueryTable
    {
        $modelRowType = $this->createModelRowType();
        $baseMethod = $this->findLastMethod('table', 'from');
        $tableExpression = $this->getStringArgument($baseMethod);

        if ($tableExpression === null) {
            return $modelRowType === null
                ? null
                : QueryTable::forModel(
                    tableName: $this->model()?->getTable(),
                    alias: null,
                    rowType: $modelRowType,
                );
        }

        [$tableName, $alias] = self::splitAlias($tableExpression);
        $alias ??= $this->getStringArgument($baseMethod, argumentIndex: 1);

        return $modelRowType === null
            ? QueryTable::fromSchema(
                tableName: $tableName,
                alias: $alias,
                connectionName: $this->connectionName,
            )
            : QueryTable::forModel(
                tableName: $tableName,
                alias: $alias,
                rowType: $modelRowType,
            );
    }


    private function createModelRowType(): ?ObjectType
    {
        $modelClassName = $this->chain->modelClassName;

        return $modelClassName === null
            ? null
            : clone $this->scope->getPhpClassInDeeperScope($modelClassName)->resolveType();
    }


    private function addJoinedTable(QueryChainMethod $method): void
    {
        $joinedTable = $this->createJoinedTable($method);

        if (! $joinedTable) {
            $this->rowIsIndeterminate = true;

            return;
        }

        if (str_starts_with($method->name, 'right')) {
            foreach ($this->tables as $table) {
                $table->makeNullable();
            }
        }

        if (str_starts_with($method->name, 'left')) {
            $joinedTable->makeNullable();
        }

        $this->tables[] = $joinedTable;
    }


    private function createJoinedTable(QueryChainMethod $method): ?QueryTable
    {
        $tableExpression = $this->getStringArgument($method);

        if ($tableExpression === null) {
            return null;
        }

        [$tableName, $alias] = self::splitAlias($tableExpression);

        return QueryTable::fromSchema(
            tableName: $tableName,
            alias: $alias,
            connectionName: $this->connectionName,
        );
    }


    private function model(): ?Model
    {
        $modelClassName = $this->chain->modelClassName;

        return $modelClassName === null ? null : ModelResolver::resolve($modelClassName);
    }


    private static function hidesTheRow(string $methodName): bool
    {
        return in_array($methodName, self::ROW_HIDING_METHODS, strict: true);
    }


    private function findLastMethod(string ...$methodNames): ?QueryChainMethod
    {
        $found = null;

        foreach ($this->chain->methods as $method) {
            if (in_array($method->name, $methodNames, strict: true)) {
                $found = $method;
            }
        }

        return $found;
    }


    private function getStringArgument(?QueryChainMethod $method, int $argumentIndex = 0): ?string
    {
        if ($method === null || ! $method->args->has($argumentIndex)) {
            return null;
        }

        $argType = $method->args->get($argumentIndex)->unwrapType($this->scope->config);

        if (! ($argType instanceof StringType)) {
            return null;
        }

        $values = $argType->getPossibleValues() ?? [];

        return count($values) === 1 ? $values[0] : null;
    }
}

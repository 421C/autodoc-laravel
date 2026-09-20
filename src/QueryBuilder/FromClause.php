<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
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
    ];

    /**
     * @var list<QueryTable>
     */
    private array $tables = [];

    private ?QueryTable $modelTable = null;

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
        $this->connectionName = $this->findLastStringArgument('connection', 'on') ?? $this->model()?->getConnectionName();
        $this->modelTable = $this->createModelTable();

        $baseTable = $this->modelTable ?? $this->createTable($this->findLastStringArgument('table', 'from'));

        if (! $baseTable) {
            return;
        }

        $this->tables = [$baseTable];

        foreach ($this->chain->methods as $method) {
            if (in_array($method->name, self::JOIN_METHODS, strict: true)) {
                $this->addJoinedTable($method->name, $method->args);

            } else if (self::hidesTheRow($method->name)) {
                $this->rowIsIndeterminate = true;
            }
        }

        if ($this->rowIsIndeterminate) {
            $this->tables = $this->modelTable ? [$this->modelTable] : [];
        }
    }


    private function addJoinedTable(string $joinMethodName, ArgumentList $args): void
    {
        $joinedTable = $this->createTable($this->getStringArgument($args));

        if (! $joinedTable) {
            $this->rowIsIndeterminate = true;

            return;
        }

        if (str_starts_with($joinMethodName, 'right')) {
            foreach ($this->tables as $table) {
                $table->makeNullable();
            }
        }

        if (str_starts_with($joinMethodName, 'left')) {
            $joinedTable->makeNullable();
        }

        $this->tables[] = $joinedTable;
    }


    private function createModelTable(): ?QueryTable
    {
        $modelClassName = $this->chain->modelClassName;

        if ($modelClassName === null) {
            return null;
        }

        return QueryTable::forModel(
            tableName: $this->model()?->getTable(),
            rowType: clone $this->scope->getPhpClassInDeeperScope($modelClassName)->resolveType(),
        );
    }


    private function createTable(?string $tableExpression): ?QueryTable
    {
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
        if (in_array($methodName, self::JOIN_METHODS, strict: true)) {
            return false;
        }

        $methodName = strtolower($methodName);

        return str_contains($methodName, 'join')
            || str_starts_with($methodName, 'union')
            || $methodName === 'fromsub'
            || $methodName === 'fromraw';
    }


    private function findLastStringArgument(string ...$methodNames): ?string
    {
        $value = null;

        foreach ($this->chain->methods as $method) {
            if (in_array($method->name, $methodNames, strict: true)) {
                $value = $this->getStringArgument($method->args) ?? $value;
            }
        }

        return $value;
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
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;

/**
 * One table a query reads from: the model or table it was rooted in, or a
 * table brought in by a join.
 */
final class QueryTable
{
    private function __construct(
        private readonly ?string $tableName,
        private readonly ?string $alias,
        private readonly ?string $connectionName,
        private ?ObjectType $rowType,
    ) {}

    private bool $rowTypeIsResolved = false;

    private bool $isNullable = false;


    public static function forModel(?string $tableName, ObjectType $rowType): self
    {
        return new self(
            tableName: $tableName,
            alias: null,
            connectionName: null,
            rowType: $rowType,
        );
    }


    public static function fromSchema(string $tableName, ?string $alias, ?string $connectionName): self
    {
        return new self(
            tableName: $tableName,
            alias: $alias,
            connectionName: $connectionName,
            rowType: null,
        );
    }


    public function matchesPrefix(string $prefix): bool
    {
        return $prefix === $this->tableName || $prefix === $this->alias;
    }


    public function makeNullable(): void
    {
        $this->isNullable = true;
    }


    public function rowType(): ?ObjectType
    {
        if ($this->rowTypeIsResolved) {
            return $this->rowType;
        }

        $this->rowTypeIsResolved = true;

        $this->rowType ??= $this->tableName === null
            ? null
            : TableSchema::resolveRowType($this->tableName, $this->connectionName);

        if ($this->rowType && $this->isNullable) {
            $this->rowType = self::withNullableColumns($this->rowType);
        }

        return $this->rowType;
    }


    private static function withNullableColumns(ObjectType $rowType): ObjectType
    {
        $nullableRowType = clone $rowType;

        $nullableRowType->properties = array_map(self::asNullable(...), $rowType->properties);

        return $nullableRowType;
    }


    private static function asNullable(Type $columnType): Type
    {
        if ($columnType instanceof NullType) {
            return $columnType;
        }

        if ($columnType instanceof UnionType
            && array_any($columnType->types, fn (Type $type) => $type instanceof NullType)
        ) {
            return $columnType;
        }

        return new UnionType([$columnType, new NullType])->setRequired($columnType->required);
    }
}

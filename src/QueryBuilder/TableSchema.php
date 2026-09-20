<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\FloatType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use Illuminate\Support\Facades\DB;
use Throwable;

final class TableSchema
{
    /** @var array<string, ?ObjectType> */
    private static array $resolved = [];


    public static function resolveRowType(string $tableName, ?string $connectionName): ?ObjectType
    {
        if (config('autodoc.laravel.offline_mode') ?? false) {
            return null;
        }

        $cacheKey = ($connectionName ?? '') . '.' . $tableName;

        if (array_key_exists($cacheKey, self::$resolved)) {
            return self::$resolved[$cacheKey];
        }

        return self::$resolved[$cacheKey] = self::readColumns($tableName, $connectionName);
    }


    public static function clearCache(): void
    {
        self::$resolved = [];
    }


    private static function readColumns(string $tableName, ?string $connectionName): ?ObjectType
    {
        try {
            $columns = DB::connection($connectionName)->getSchemaBuilder()->getColumns($tableName);

        } catch (Throwable) {
            return null;
        }

        if (! $columns) {
            return null;
        }

        $rowType = new ObjectType;

        foreach ($columns as $column) {
            $columnType = self::typeFromColumnTypeName($column['type_name']);

            if ($column['nullable']) {
                $columnType = new UnionType([$columnType, new NullType]);
            }

            $rowType->properties[$column['name']] = $columnType->setRequired(true);
        }

        return $rowType;
    }


    public static function typeFromColumnTypeName(string $typeName): Type
    {
        $typeName = strtolower($typeName);
        $typeName = preg_replace('/\([^)]*\)/', '', $typeName) ?? $typeName;
        $typeName = trim(preg_replace('/\s+/', ' ', $typeName) ?? $typeName);

        return match ($typeName) {
            'bit',
            'bigint',
            'bigserial',
            'int',
            'int2',
            'int4',
            'int8',
            'integer',
            'mediumint',
            'serial',
            'smallint',
            'smallserial',
            'tinyint',
            'year' => new IntegerType,

            'decimal',
            'double',
            'double precision',
            'float',
            'float4',
            'float8',
            'money',
            'numeric',
            'real' => new FloatType,

            'binary',
            'blob',
            'bpchar',
            'char',
            'character',
            'character varying',
            'cidr',
            'citext',
            'inet',
            'json',
            'jsonb',
            'macaddr',
            'macaddr8',
            'nchar',
            'nvarchar',
            'string',
            'text',
            'uniqueidentifier',
            'uuid',
            'varbinary',
            'varchar',
            'xml' => new StringType,

            'datetime',
            'timestamp',
            'timestamp without time zone',
            'timestamp with time zone',
            'timestamptz' => new StringType(format: 'date-time'),
            'date' => new StringType(format: 'date'),
            'time',
            'time without time zone',
            'time with time zone',
            'timetz' => new StringType(format: 'time'),
            'bool', 'boolean' => new BoolType,
            default => new UnknownType,
        };
    }
}

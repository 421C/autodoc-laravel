<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\PhpClass;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\NumberType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class RelationAggregate
{
    private const FUNCTIONS = [
        'withcount' => 'count',
        'withexists' => 'exists',
        'withmin' => 'min',
        'withmax' => 'max',
        'withsum' => 'sum',
        'withavg' => 'avg',
        'loadcount' => 'count',
        'loadexists' => 'exists',
        'loadmin' => 'min',
        'loadmax' => 'max',
        'loadsum' => 'sum',
        'loadavg' => 'avg',
    ];

    public function __construct(
        /** @var PhpClass<Model> */
        private PhpClass $modelPhpClass,
        string $relationExpression,
        public readonly string $function,
        public readonly string $column,
    ) {
        [$this->relationName, $this->alias] = $this->splitAliasedRelationExpression($relationExpression);
    }

    public readonly string $relationName;

    public readonly string $alias;


    /**
     * @param class-string<Model> $modelClassName
     * @return ?list<self>
     */
    public static function parse(string $methodName, ArgumentList $args, string $modelClassName, Scope $scope): ?array
    {
        $function = self::FUNCTIONS[strtolower($methodName)] ?? null;

        if ($function === null || count($args) === 0) {
            return null;
        }

        $column = self::takesNoColumnArgument($function)
            ? '*'
            : self::resolveColumnArgument($args, $scope);

        if ($column === null) {
            return [];
        }

        $modelPhpClass = $scope->getPhpClassInDeeperScope($modelClassName);

        return array_map(
            fn (string $relationExpression) => new self(
                modelPhpClass: $modelPhpClass,
                relationExpression: $relationExpression,
                function: $function,
                column: $column,
            ),
            self::resolveRelationExpressions($args, $function, $scope),
        );
    }


    public function resolveType(): Type
    {
        $type = match ($this->function) {
            'count' => new IntegerType(minimum: 0),
            'exists' => new BoolType,
            'min', 'max' => $this->resolveRelatedColumnType() ?? new NumberType,
            default => new NumberType,
        };

        if ($this->isNeverNull()) {
            return $type->setRequired(true);
        }

        return (new UnionType([$type, new NullType]))
            ->unwrapType($this->modelPhpClass->scope->config)
            ->setRequired(true);
    }


    private function isNeverNull(): bool
    {
        return $this->function === 'count' || $this->function === 'exists';
    }


    private static function takesNoColumnArgument(string $function): bool
    {
        return $function === 'count' || $function === 'exists';
    }


    private static function acceptsVariadicRelations(string $function): bool
    {
        return $function === 'count';
    }


    private function resolveRelatedColumnType(): ?Type
    {
        if (str_contains($this->column, '.') || str_contains($this->column, '->')) {
            return null;
        }

        return (new Relation(
            modelPhpClass: $this->modelPhpClass,
            name: $this->relationName,
        ))->getRelatedColumnType($this->column);
    }


    /**
     * @return array{string, string}
     */
    private function splitAliasedRelationExpression(string $relationExpression): array
    {
        $segments = explode(' ', $relationExpression);

        if (count($segments) === 3 && Str::lower($segments[1]) === 'as') {
            return [$segments[0], $segments[2]];
        }

        return [$relationExpression, $this->buildAlias($relationExpression)];
    }


    private function buildAlias(string $relationName): string
    {
        return Str::snake((string) preg_replace(
            '/[^[:alnum:][:space:]_]/u',
            '',
            $relationName . ' ' . $this->function . ' ' . strtolower($this->column),
        ));
    }


    private static function resolveColumnArgument(ArgumentList $args, Scope $scope): ?string
    {
        $index = $args->indexForParameter('column', 1);

        if ($index === null) {
            return null;
        }

        $columnType = $args->get($index)->unwrapType($scope->config);

        if (! ($columnType instanceof StringType)) {
            return null;
        }

        $values = $columnType->getPossibleValues() ?? [];

        return count($values) === 1 ? $values[0] : null;
    }


    /**
     * @return list<string>
     */
    private static function resolveRelationExpressions(ArgumentList $args, string $function, Scope $scope): array
    {
        return $scope->withPartialArraysResolvingAsShapes(function () use ($args, $function, $scope) {
            $expressions = [];

            foreach (self::relationArgumentIndexes($args, $function) as $index) {
                $expressions = [...$expressions, ...self::stringValuesIn($args->get($index), $scope)];
            }

            return $expressions;
        });
    }


    /**
     * @return list<int>
     */
    private static function relationArgumentIndexes(ArgumentList $args, string $function): array
    {
        if (! self::acceptsVariadicRelations($function)) {
            $index = $args->findNamedIndex('relation') ?? $args->indexForParameter('relations', 0);

            return $index === null ? [] : [$index];
        }

        $namedIndex = $args->findNamedIndex('relations');

        if ($namedIndex !== null) {
            return [$namedIndex];
        }

        return $args->positionalCount() > 0 ? range(0, $args->positionalCount() - 1) : [];
    }


    /**
     * @return list<string>
     */
    private static function stringValuesIn(Type $type, Scope $scope): array
    {
        $type = $type->unwrapType($scope->config);

        if ($type instanceof StringType) {
            return $type->getPossibleValues() ?? [];
        }

        if ($type instanceof UnionType) {
            return self::stringValuesInEach($type->types, $scope);
        }

        if (! ($type instanceof ArrayType)) {
            return [];
        }

        $values = [];

        foreach ($type->shape as $key => $valueType) {
            if (is_string($key)) {
                $values[] = $key;

            } else {
                $values = [...$values, ...self::stringValuesIn($valueType, $scope)];
            }
        }

        if (! $type->shape && $type->itemType) {
            $values = self::stringValuesIn($type->itemType, $scope);
        }

        return $values;
    }


    /**
     * @param array<Type> $types
     * @return list<string>
     */
    private static function stringValuesInEach(array $types, Scope $scope): array
    {
        $values = [];

        foreach ($types as $type) {
            $values = [...$values, ...self::stringValuesIn($type, $scope)];
        }

        return $values;
    }
}

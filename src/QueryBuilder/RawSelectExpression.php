<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Config;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Laravel\Helpers\ParsesSqlExpressions;
use Illuminate\Contracts\Database\Query\Expression;

/**
 * One expression of a raw select list, as written in `selectRaw()` or in an
 * `Expression` passed to `select()`/`addSelect()`.
 */
final class RawSelectExpression
{
    use ParsesSqlExpressions;

    private function __construct(
        public readonly string $expression,
        public readonly ?string $alias,
        public readonly ?string $functionName,
        public readonly ?string $functionArgument,
    ) {}

    private const PLAIN_COLUMN_PATTERN = '/^(\*|[A-Za-z_]\w*(\.\w+)*(\.\*)?(->\w+)*)$/';


    /**
     * @return list<self>
     */
    public static function parseList(string $selectList): array
    {
        $parsed = [];

        foreach (self::splitTopLevelCommas($selectList) ?? [] as $part) {
            [$expression, $alias] = self::splitAlias($part);

            if ($expression === '') {
                continue;
            }

            $expression = self::unwrapIdentifierPath($expression);
            $alias = $alias === null ? null : self::unwrapIdentifier($alias);

            [$functionName, $functionArgument] = self::splitFunctionCall($expression);

            $parsed[] = new self(
                expression: $expression,
                alias: $alias === '' ? null : $alias,
                functionName: $functionName,
                functionArgument: $functionArgument === null ? null : self::unwrapIdentifierPath($functionArgument),
            );
        }

        return $parsed;
    }


    /**
     * The SQL an `Expression` was built from, readable whenever the analyzer
     * kept the literal it was constructed with.
     */
    public static function readExpressionArgument(Type $type, Config $config): ?string
    {
        if (! ($type instanceof ObjectType) || ! $type->className || ! is_a($type->className, Expression::class, true)) {
            return null;
        }

        if (! $type->constructorArgs?->has(0)) {
            return null;
        }

        $valueType = $type->constructorArgs->get(0)->unwrapType($config);

        if (! ($valueType instanceof StringType)) {
            return null;
        }

        $values = $valueType->getPossibleValues() ?? [];

        return count($values) === 1 ? $values[0] : null;
    }


    public static function isPlainColumn(string $expression): bool
    {
        return preg_match(self::PLAIN_COLUMN_PATTERN, $expression) === 1;
    }


    public function isPlainColumnReference(): bool
    {
        return self::isPlainColumn($this->expression);
    }


    /**
     * @return array{?string, ?string}
     */
    private static function splitFunctionCall(string $expression): array
    {
        if (preg_match('/^([A-Za-z_]\w*)\s*\((.*)\)$/s', $expression, $matches) !== 1) {
            return [null, null];
        }

        if (self::splitTopLevelCommas($matches[2]) === null) {
            return [null, null];
        }

        return [strtolower($matches[1]), trim($matches[2])];
    }
}

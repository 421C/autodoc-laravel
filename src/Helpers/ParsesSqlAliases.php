<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

/**
 * Splits the `expression as alias` form SQL accepts for both tables and columns.
 */
trait ParsesSqlAliases
{
    /**
     * @return array{string, ?string}
     */
    protected function splitAlias(string $expression): array
    {
        $parts = preg_split('/\s+as\s+/i', $expression, 2);

        if ($parts && count($parts) === 2) {
            return [trim($parts[0]), trim($parts[1])];
        }

        return [trim($expression), null];
    }
}

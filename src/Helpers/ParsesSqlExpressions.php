<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

/**
 * Splits SQL expression text: the `expression as alias` form both tables and
 * columns accept, and comma-separated expression lists. Quoted strings and
 * parenthesized subexpressions are never split inside.
 */
trait ParsesSqlExpressions
{
    /**
     * @return array{string, ?string}
     */
    protected static function splitAlias(string $expression): array
    {
        $keywords = self::findTopLevelMatches($expression, '\s+as\s+');

        if (! $keywords) {
            return [trim($expression), null];
        }

        [$offset, $length] = $keywords[count($keywords) - 1];

        return [
            trim(substr($expression, 0, $offset)),
            trim(substr($expression, $offset + $length)),
        ];
    }


    /**
     * @return ?list<string> null when quotes or parentheses do not balance
     */
    protected static function splitTopLevelCommas(string $expressionList): ?array
    {
        $commas = self::findTopLevelMatches($expressionList, ',');

        if ($commas === null) {
            return null;
        }

        $expressions = [];
        $start = 0;

        foreach ($commas as [$offset, $length]) {
            $expressions[] = substr($expressionList, $start, $offset - $start);
            $start = $offset + $length;
        }

        $expressions[] = substr($expressionList, $start);

        return $expressions;
    }


    /**
     * @return ?list<array{int, int}> offset and length of each match, null when
     *                                quotes or parentheses do not balance
     */
    private static function findTopLevelMatches(string $sql, string $pattern): ?array
    {
        preg_match_all('/' . $pattern . '/i', $sql, $found, PREG_OFFSET_CAPTURE);

        $candidates = [];

        foreach ($found[0] as [$text, $candidateOffset]) {
            $candidates[$candidateOffset] = strlen($text);
        }

        $matches = [];
        $depth = 0;
        $quote = null;
        $length = strlen($sql);

        for ($offset = 0; $offset < $length; $offset++) {
            $character = $sql[$offset];

            if ($quote !== null) {
                if ($character === '\\') {
                    $offset++;

                } else if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"' || $character === '`') {
                $quote = $character;

                continue;
            }

            if ($character === '(') {
                $depth++;

                continue;
            }

            if ($character === ')') {
                $depth--;

                if ($depth < 0) {
                    return null;
                }

                continue;
            }

            if ($depth > 0 || ! isset($candidates[$offset])) {
                continue;
            }

            $matches[] = [$offset, $candidates[$offset]];
            $offset += $candidates[$offset] - 1;
        }

        return $quote === null && $depth === 0 ? $matches : null;
    }
}

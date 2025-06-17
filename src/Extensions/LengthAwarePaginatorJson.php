<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\ClassExtension;
use AutoDoc\Laravel\QueryBuilder\Paginator;

/**
 * Handles `Illuminate\Pagination\LengthAwarePaginator` responses.
 */
class LengthAwarePaginatorJson extends ClassExtension
{
    public function getReturnType(PhpClass $phpClass): ?Type
    {
        if (! is_a($phpClass->className, \Illuminate\Pagination\LengthAwarePaginator::class, true)) {
            return null;
        }

        return (new Paginator(
            paginatorPhpClass: $phpClass,
            entryClass: null,
        ))->resolveType();
    }
}

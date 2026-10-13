<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Services;

class Catalog
{
    /**
     * @return list<int>
     */
    public static function pageSizes(): array
    {
        return array_map(intval(...), explode(',', (string) getenv('PAGE_SIZES')));
    }


    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return explode(',', (string) getenv('CATALOG_NAMES'));
    }
}

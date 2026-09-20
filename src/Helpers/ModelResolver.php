<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use Illuminate\Database\Eloquent\Model;
use Throwable;

final class ModelResolver
{
    /** @var array<string, ?Model> */
    private static array $resolved = [];


    public static function resolve(string $className): ?Model
    {
        if (array_key_exists($className, self::$resolved)) {
            return self::$resolved[$className];
        }

        try {
            $model = app()->make($className);

        } catch (Throwable) {
            $model = null;
        }

        return self::$resolved[$className] = $model instanceof Model ? $model : null;
    }


    public static function clearCache(): void
    {
        self::$resolved = [];
    }
}

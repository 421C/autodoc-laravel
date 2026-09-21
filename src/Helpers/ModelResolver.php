<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use Illuminate\Database\Eloquent\Model;
use ReflectionProperty;
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


    /**
     * @return list<string>
     */
    public static function defaultEagerLoads(string $className): array
    {
        return self::configuredRelationNames($className, 'with');
    }


    /**
     * @return list<string>
     */
    public static function defaultEagerLoadCounts(string $className): array
    {
        return self::configuredRelationNames($className, 'withCount');
    }


    /**
     * @return list<string>
     */
    private static function configuredRelationNames(string $className, string $propertyName): array
    {
        $model = self::resolve($className);
        $names = $model ? new ReflectionProperty($model, $propertyName)->getValue($model) : null;

        if (! is_array($names)) {
            return [];
        }

        return array_values(array_filter($names, is_string(...)));
    }


    public static function clearCache(): void
    {
        self::$resolved = [];
    }
}

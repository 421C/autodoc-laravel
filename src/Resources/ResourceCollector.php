<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Resources;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;


trait ResourceCollector
{
    /**
     * @param PhpClass<object> $phpClass
     */
    protected function getCollectionType(PhpClass $phpClass): ArrayType
    {
        $collectionClassInstance = $phpClass->getReflection()->newInstanceWithoutConstructor();

        if (isset($collectionClassInstance->collects)) {
            $resourceClassName = $collectionClassInstance->collects;

        } else if (str_ends_with($collectionClassInstance::class, 'Collection')
            && (
                class_exists($class = Str::replaceLast('Collection', '', $collectionClassInstance::class))
                || class_exists($class = Str::replaceLast('Collection', 'Resource', $collectionClassInstance::class))
            )
        ) {
            $resourceClassName = $class;
        }

        if (isset($resourceClassName)) {
            /** @var class-string<object> $resourceClassName */
            $resourceType = $phpClass->scope->getPhpClassInDeeperScope($resourceClassName)->resolveType();

        } else {
            if ($phpClass->scope->isDebugModeEnabled()) {
                throw new Exception('Error resolving resource class collected by "' . $phpClass->className . '"');
            }

            $resourceType = new ObjectType;
        }

        return new ArrayType($resourceType, className: Collection::class);
    }
}

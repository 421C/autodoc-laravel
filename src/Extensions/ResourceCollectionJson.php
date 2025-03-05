<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\ClassExtension;
use AutoDoc\Laravel\Resources\ResourceCollector;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Handles `Illuminate\Http\Resources\Json\ResourceCollection` responses.
 */
class ResourceCollectionJson extends ClassExtension
{
    use ResourceCollector;

    public function getReturnType(PhpClass $phpClass): ?Type
    {
        if (! is_subclass_of($phpClass->className, ResourceCollection::class)) {
            return null;
        }

        $toArrayMethod = $phpClass->getMethod('toArray');

        if ($toArrayMethod->exists()) {
            $collectionType = $toArrayMethod->getReturnType(doNotAnalyzeBody: true);

            if (! ($collectionType instanceof ArrayType && $collectionType->shape)) {
                $collectionType = $toArrayMethod->getReturnType(usePhpDocIfAvailable: false);
            }
        }

        if (empty($collectionType) || !($collectionType instanceof ArrayType && $collectionType->shape)) {
            $collectionType = $this->getCollectionType($phpClass);
        }

        if ($phpClass->isFinalResponse && !empty($phpClass->className::$wrap)) {
            if (isset($collectionType->shape[$phpClass->className::$wrap])) {
                return $collectionType;
            }

            $collectionType = new ArrayType(shape: [
                $phpClass->className::$wrap => $collectionType,
            ]);
        }

        return $collectionType;
    }


    public function getPropertyType(PhpClass $phpClass, string $propertyName): ?Type
    {
        if ($propertyName !== 'collection') {
            return null;
        }

        if (! is_subclass_of($phpClass->className, ResourceCollection::class)) {
            return null;
        }

        return $this->getCollectionType($phpClass);
    }
}

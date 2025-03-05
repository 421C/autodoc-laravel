<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\ClassExtension;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Handles `Illuminate\Http\Resources\Json\JsonResource` responses.
 */
class ResourceJson extends ClassExtension
{
    public function getReturnType(PhpClass $phpClass): ?Type
    {
        if (! is_subclass_of($phpClass->className, JsonResource::class)) {
            return null;
        }

        $toArrayMethod = $phpClass->getMethod('toArray');

        if ($toArrayMethod->exists()) {
            $resultType = $toArrayMethod->getReturnType(doNotAnalyzeBody: true);

            if (! ($resultType instanceof ArrayType && $resultType->shape)) {
                $resultType = $toArrayMethod->getReturnType(usePhpDocIfAvailable: false);
            }
        }

        if (empty($resultType) || !($resultType instanceof ArrayType && $resultType->shape)) {
            $resultType = new ObjectType;
        }

        return $resultType;
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\Extensions\TypeScriptExportExtension;
use Illuminate\Database\Eloquent\Model;

/**
 * Marks model properties as required for TypeScript export.
 */
class EloquentModelTypeScriptExport extends TypeScriptExportExtension
{
    public function handle(PhpClass $phpClass, Type $type): ?Type
    {
        if (! is_subclass_of($phpClass->className, Model::class)) {
            return null;
        }

        if ($type instanceof ObjectType) {
            foreach ($type->properties as $propertyName => $propertyType) {
                $type->properties[$propertyName]->required = true;
            }

        } else if ($type instanceof ArrayType && $type->shape) {
            foreach ($type->shape as $propertyName => $propertyType) {
                $type->shape[$propertyName]->required = true;
            }
        }

        return $type;
    }
}

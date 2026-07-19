<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\Extensions\OperationExtension;
use AutoDoc\Laravel\Helpers\InspectsModelAttributes;
use AutoDoc\OpenApi\Operation;
use AutoDoc\OpenApi\Response;
use AutoDoc\Route;
use Illuminate\Database\Eloquent\Model;
use SplObjectStorage;

/**
 * Normalizes Eloquent response types for schema generation using model
 * visibility rules and model-level types for transformed attributes.
 */
class EloquentModelSerialization extends OperationExtension
{
    use InspectsModelAttributes;

    public function handle(Operation $operation, Route $route, Scope $scope): ?Operation
    {
        /** @var SplObjectStorage<Type, null> */
        $visitedTypes = new SplObjectStorage;

        foreach ($operation->responses ?? [] as $response) {
            if (! ($response instanceof Response)) {
                continue;
            }

            foreach ($response->content ?? [] as $mediaType) {
                $mediaType->type = $this->normalizeTypeTree($mediaType->type, $scope, $visitedTypes);
            }
        }

        return $operation;
    }


    /**
     * @param SplObjectStorage<Type, null> $visitedTypes
     */
    private function normalizeTypeTree(Type $type, Scope $scope, SplObjectStorage $visitedTypes): Type
    {
        $unwrappedType = $type->unwrapType($scope->config);

        if (! $visitedTypes->contains($unwrappedType)) {
            $visitedTypes->attach($unwrappedType);

            $this->normalizeChildTypes($unwrappedType, $scope, $visitedTypes);
        }

        // An unresolved wrapper keeps its own `required` flag visible to the
        // containing shape until serialization, so its resolved replacement
        // must not leak a different flag into the parent's required list.
        if ($unwrappedType === $type || $unwrappedType->required === $type->required) {
            return $unwrappedType;
        }

        $requiredPreservingType = clone $unwrappedType;
        $requiredPreservingType->required = $type->required;

        return $requiredPreservingType;
    }


    /**
     * @param SplObjectStorage<Type, null> $visitedTypes
     */
    private function normalizeChildTypes(Type $type, Scope $scope, SplObjectStorage $visitedTypes): void
    {
        if ($type instanceof UnionType) {
            foreach ($type->types as $index => $variantType) {
                $type->types[$index] = $this->normalizeTypeTree($variantType, $scope, $visitedTypes);
            }

        } else if ($type instanceof ArrayType) {
            foreach ($type->shape as $key => $shapeItemType) {
                $type->shape[$key] = $this->normalizeTypeTree($shapeItemType, $scope, $visitedTypes);
            }

            if ($type->itemType !== null) {
                $type->itemType = $this->normalizeTypeTree($type->itemType, $scope, $visitedTypes);
            }

        } else if ($type instanceof ObjectType) {
            if ($type->className !== null && is_subclass_of($type->className, Model::class)) {
                $type->properties = $this->normalizeSerializedModelProperties(
                    scope: $scope,
                    modelClassName: $type->className,
                    properties: $type->properties,
                );
            }

            foreach ($type->properties as $propertyName => $propertyType) {
                $type->properties[$propertyName] = $this->normalizeTypeTree($propertyType, $scope, $visitedTypes);
            }

            if ($type->typeToDisplay !== null) {
                $type->typeToDisplay = $this->normalizeTypeTree($type->typeToDisplay, $scope, $visitedTypes);
            }
        }
    }
}

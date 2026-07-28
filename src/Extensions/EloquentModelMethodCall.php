<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallContext;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\Helpers\InspectsModelAttributes;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use ReflectionMethod;

/**
 * Handles method calls on `Illuminate\Database\Eloquent\Model` class.
 */
class EloquentModelMethodCall extends MethodCallExtension
{
    use InspectsModelAttributes;

    public function handleSideEffect(MethodCallContext $call): void
    {
        if ($call->methodName === 'setAttribute') {
            $this->handleSetAttribute($call);
        }
    }


    private function handleSetAttribute(MethodCallContext $call): void
    {
        $modelType = $this->getModelType($call);

        if ($modelType === null) {
            return;
        }

        $attribute = $this->parseSetAttributeCall($call, $modelType);

        if ($attribute !== null) {
            [$name, $type] = $attribute;

            if ($this->mutateNullablePropertyReceiver($call, $modelType, $name, $type)) {
                return;
            }

            if ($call->node instanceof NullsafeMethodCall
                && $this->typeIncludesNull($call->getVarType())
            ) {
                return;
            }

            $call->mutateExpression($call->node->var, [$name => clone $type]);
        }
    }


    public function getReturnType(MethodCallContext $call): ?Type
    {
        if (! in_array($call->methodName, [
            'setAttribute',
            'getAttribute',
            'attributesToArray',
            'toArray',
        ])) {
            return null;
        }

        $modelType = $this->getModelType($call);

        if ($modelType === null) {
            return null;
        }

        $returnType = match ($call->methodName) {
            'setAttribute' => $this->getSetAttributeReturnType($call, $modelType),
            'getAttribute' => $this->getGetAttributeReturnType($call, $modelType),
            'attributesToArray' => $this->resolveAttributesArrayType($call, $modelType),
            default => $this->getToArrayReturnType($call, $modelType),
        };

        if ($returnType !== null
            && $call->node instanceof NullsafeMethodCall
            && $this->typeIncludesNull($call->getVarType())
        ) {
            return new UnionType([$returnType, new NullType])->unwrapType($call->scope->config);
        }

        return $returnType;
    }


    private function getSetAttributeReturnType(MethodCallContext $call, ObjectType $modelType): ObjectType
    {
        $returnType = clone $modelType;
        $attribute = $this->parseSetAttributeCall($call, $modelType);

        if ($attribute !== null) {
            [$name, $type] = $attribute;

            $returnType->properties[$name] = clone $type;
        }

        return $returnType;
    }


    /**
     * Resolves `getAttribute($key)` like the property read `$model->$key`: the
     * model's own attribute types (columns, casts, accessors, relations) first,
     * then attributes set earlier on this variable.
     */
    private function getGetAttributeReturnType(MethodCallContext $call, ObjectType $modelType): ?Type
    {
        $key = $this->getLiteralKeyArgument($call);
        $className = $modelType->className;

        if ($key === null || $className === null || str_contains($key, '->')) {
            return null;
        }

        $phpClass = $call->scope->getPhpClassInDeeperScope($className);

        $propertyType = (new EloquentModel)->getPropertyType($phpClass, $key)
            ?? $modelType->properties[$key]
            ?? $modelType->hiddenProperties[$key]
            ?? null;

        return $propertyType ? clone $propertyType : null;
    }


    private function getToArrayReturnType(MethodCallContext $call, ObjectType $modelType): ?Type
    {
        $className = $modelType->className;

        if ($className === null) {
            return null;
        }

        $phpClass = $call->scope->getPhpClassInDeeperScope($className);

        $modelToArrayMethod = $phpClass->getMethod('toArray');

        $modelToArrayMethodReflection = $modelToArrayMethod->getReflection();
        $modelToArrayMethodDeclaringClass = $modelToArrayMethodReflection instanceof ReflectionMethod
            ? $modelToArrayMethodReflection->getDeclaringClass()->getName()
            : null;

        // A custom toArray() defines its own shape; analyze its return type.
        if ($modelToArrayMethodDeclaringClass !== null && $modelToArrayMethodDeclaringClass !== Model::class) {
            $modelArrayRepresentation = $modelToArrayMethod->getReturnType()->unwrapType($phpClass->scope->config);

            if ($modelArrayRepresentation instanceof ArrayType) {
                if (! $modelArrayRepresentation->shape && ! isset($modelArrayRepresentation->itemType)) {
                    $modelArrayRepresentation->itemType = new UnknownType;
                }

                return $modelArrayRepresentation;
            }

            return null;
        }

        // Base Model toArray(): the model's attribute shape.
        return $this->resolveAttributesArrayType($call, $modelType);
    }


    /**
     * The attribute shape for `attributesToArray()` and base-Model `toArray()`.
     * Prefers the variable's resolved properties (set attributes, `select()`
     * subsets), falling back to the class-level attribute shape.
     */
    private function resolveAttributesArrayType(MethodCallContext $call, ObjectType $modelType): ?ArrayType
    {
        if ($modelType->className === null) {
            return $modelType->properties !== [] ? new ArrayType(shape: $modelType->properties) : null;
        }

        if ($modelType->properties !== []) {
            return new ArrayType(shape: $this->normalizeSerializedModelProperties(
                scope: $call->scope,
                modelClassName: $modelType->className,
                properties: $modelType->properties,
            ));
        }

        return (new EloquentModel)->getModelAttributesArrayType($call->scope, $modelType->className);
    }


    /**
     * @return array{string, Type}|null
     */
    private function parseSetAttributeCall(MethodCallContext $call, ObjectType $modelType): ?array
    {
        $valueIndex = $call->argTypes->indexForParameter('value', 1);
        $key = $this->getLiteralKeyArgument($call);

        if ($key === null || $valueIndex === null) {
            return null;
        }

        // Laravel routes keys containing `->` into a JSON column write, so no
        // attribute exists under that name.
        if (str_contains($key, '->')) {
            return null;
        }

        $model = $modelType->className ? $this->makeModel($modelType->className) : null;
        $valueType = clone $call->argTypes->get($valueIndex)->unwrapType($call->scope->config);

        if ($model) {
            if ($this->isAttributeValueTransformed($model, $key)
                && ! $this->castKeepsAssignedValueType($model, $key, $valueType)
            ) {
                // The assigned value changes on write (cast, date, or set
                // mutator): keep the resolved attribute type when the model
                // has one, otherwise record the attribute without a type.
                return isset($modelType->properties[$key]) || isset($modelType->hiddenProperties[$key])
                    ? null
                    : [$key, (new UnknownType)->setRequired(true)];
            }
        }

        return [$key, $valueType->setRequired(true)];
    }


    /**
     * The literal `key` argument of a `getAttribute()`/`setAttribute()` call, or
     * null if it is not a single string literal.
     */
    private function getLiteralKeyArgument(MethodCallContext $call): ?string
    {
        $keyIndex = $call->argTypes->indexForParameter('key', 0);

        if ($keyIndex === null) {
            return null;
        }

        $keyType = $call->argTypes->get($keyIndex)->unwrapType($call->scope->config);

        if (! ($keyType instanceof StringType)) {
            return null;
        }

        $keys = $keyType->getPossibleValues();

        if ($keys === null || count($keys) !== 1) {
            return null;
        }

        return (string) $keys[0];
    }


    private function getModelType(MethodCallContext $call): ?ObjectType
    {
        return $this->resolveModelObjectType($call->getVarType());
    }


    /**
     * Nullable relation receivers expose one model alongside null, so reject
     * unions that do not resolve to exactly one model type.
     */
    private function resolveModelObjectType(Type $type): ?ObjectType
    {
        $variants = $type instanceof UnionType ? $type->types : [$type];
        $modelType = null;

        foreach ($variants as $variant) {
            if ($variant instanceof NullType) {
                continue;
            }

            if (! ($variant instanceof ObjectType)
                || ! $variant->className
                || ! is_subclass_of($variant->className, Model::class)
            ) {
                return null;
            }

            if ($modelType !== null) {
                return null;
            }

            $modelType = $variant;
        }

        return $modelType;
    }


    private function typeIncludesNull(Type $type): bool
    {
        if ($type instanceof NullType) {
            return true;
        }

        return $type instanceof UnionType
            && array_any($type->types, $this->typeIncludesNull(...));
    }


    /**
     * Replaces a nullable relation on its parent so recording the mutation
     * does not discard the null variant.
     */
    private function mutateNullablePropertyReceiver(
        MethodCallContext $call,
        ObjectType $modelType,
        string $attributeName,
        Type $attributeType,
    ): bool {
        if (! ($call->node instanceof NullsafeMethodCall)
            || ! $this->typeIncludesNull($call->getVarType())
            || ! ($call->node->var instanceof PropertyFetch
                || $call->node->var instanceof NullsafePropertyFetch)
        ) {
            return false;
        }

        $propertyName = $call->scope->getRawValueFromNode($call->node->var->name);

        if (! is_string($propertyName)) {
            return false;
        }

        $mutatedModelType = clone $modelType;
        $mutatedModelType->properties[$attributeName] = clone $attributeType;
        $receiverType = $call->getVarType();
        $receiverVariants = $receiverType instanceof UnionType ? $receiverType->types : [$receiverType];

        $updatedReceiverVariants = array_map(
            fn (Type $variant): Type => $variant === $modelType ? $mutatedModelType : $variant,
            $receiverVariants,
        );

        $updatedReceiverType = new UnionType($updatedReceiverVariants)
            ->unwrapType($call->scope->config)
            ->setRequired($receiverType->required);

        $call->mutateExpression($call->node->var->var, [
            $propertyName => $updatedReceiverType,
        ]);

        return true;
    }
}

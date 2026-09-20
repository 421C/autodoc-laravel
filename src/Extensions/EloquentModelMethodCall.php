<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\PhpClass;
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
use AutoDoc\Laravel\Helpers\ModelResolver;
use AutoDoc\Laravel\Helpers\ModelVisibility;
use AutoDoc\Laravel\Helpers\MutatesModelReceiver;
use AutoDoc\Laravel\Helpers\ParsesKeyListArguments;
use AutoDoc\Laravel\QueryBuilder\Relation;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\Variable;
use ReflectionMethod;

/**
 * Handles method calls on `Illuminate\Database\Eloquent\Model` class.
 */
class EloquentModelMethodCall extends MethodCallExtension
{
    use InspectsModelAttributes, MutatesModelReceiver, ParsesKeyListArguments;

    private const METHODS = [
        'setAttribute',
        'getAttribute',
        'getKey',
        'only',
        'except',
        'attributesToArray',
        'toArray',
        ...ModelVisibility::METHODS,
    ];


    public function handleSideEffect(MethodCallContext $call): void
    {
        if ($call->methodName === 'setAttribute') {
            $this->handleSetAttribute($call);

            return;
        }

        if (in_array($call->methodName, ModelVisibility::METHODS, true)) {
            $this->handleVisibilityChange($call);
        }
    }


    private function handleVisibilityChange(MethodCallContext $call): void
    {
        $node = $call->node;

        if (! ($node instanceof MethodCall)
            || ! ($node->var instanceof Variable)
            || ! is_string($node->var->name)
        ) {
            return;
        }

        $modelType = $this->getModelType($call);

        if ($modelType === null) {
            return;
        }

        $repartitionedType = ModelVisibility::apply($call, $modelType);

        if ($repartitionedType !== null) {
            $call->setVarType($node->var->name, $repartitionedType);
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

            $this->mutateModelReceiver($call, $modelType, [$name => $type]);
        }
    }


    public function getReturnType(MethodCallContext $call): ?Type
    {
        if (! in_array($call->methodName, self::METHODS, true)) {
            return null;
        }

        $modelType = $this->getModelType($call);

        if ($modelType === null) {
            return null;
        }

        $returnType = match ($call->methodName) {
            'setAttribute' => $this->getSetAttributeReturnType($call, $modelType),
            'getAttribute' => $this->getGetAttributeReturnType($call, $modelType),
            'getKey' => $this->getKeyReturnType($call, $modelType),
            'only' => $this->getOnlyReturnType($call, $modelType),
            'except' => $this->getExceptReturnType($call, $modelType),
            'attributesToArray' => $this->resolveAttributesArrayType($call, $modelType),
            'toArray' => $this->getToArrayReturnType($call, $modelType),
            default => ModelVisibility::apply($call, $modelType) ?? clone $modelType,
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


    private function getGetAttributeReturnType(MethodCallContext $call, ObjectType $modelType): ?Type
    {
        $key = $this->getLiteralKeyArgument($call);

        return $key === null ? null : $this->resolveAttributeType($call, $modelType, $key);
    }


    private function getKeyReturnType(MethodCallContext $call, ObjectType $modelType): ?Type
    {
        $model = $modelType->className ? ModelResolver::resolve($modelType->className) : null;

        if ($model === null) {
            return null;
        }

        return $this->resolveAttributeType($call, $modelType, $model->getKeyName())
            ?? $this->modelKeyType($model);
    }


    /**
     * `only()` and `except()` read through `getAttribute()`, so they ignore
     * `$hidden`/`$visible` and return a plain array rather than a model.
     */
    private function getOnlyReturnType(MethodCallContext $call, ObjectType $modelType): ?ArrayType
    {
        $className = $modelType->className;
        $keyNames = $this->resolveKeyListNames($call->argTypes, $call->scope->config, allowVariadic: true);

        if ($keyNames === [] || $className === null || ! $this->modelAttributesAreResolved($call, $className)) {
            return null;
        }

        $shape = [];

        foreach ($keyNames as $keyName) {
            $shape[$keyName] = ($this->resolveAttributeType($call, $modelType, $keyName) ?? new NullType)
                ->setRequired(true);
        }

        return new ArrayType(shape: $shape);
    }


    /**
     * @param class-string $className
     */
    private function modelAttributesAreResolved(MethodCallContext $call, string $className): bool
    {
        return $call->scope->getPhpClassInDeeperScope($className)->resolveType()->hasResolvedShape();
    }


    /**
     * `except()` walks `getAttributes()`, which holds loaded columns only, so
     * appended accessors and eager-loaded relations are not part of the result.
     */
    private function getExceptReturnType(MethodCallContext $call, ObjectType $modelType): ?ArrayType
    {
        $className = $modelType->className;
        $attributes = array_merge($modelType->properties, $modelType->hiddenProperties);

        if ($className === null || $attributes === []) {
            return null;
        }

        $excludedKeyNames = $this->resolveKeyListNames($call->argTypes, $call->scope->config, allowVariadic: true);

        if ($excludedKeyNames === []) {
            return null;
        }

        $phpClass = $call->scope->getPhpClassInDeeperScope($className);

        /** @var PhpClass<Model> $phpClass */

        $appends = ModelResolver::resolve($className)?->getAppends() ?? [];
        $shape = [];

        foreach ($attributes as $keyName => $attributeType) {
            if (in_array($keyName, $excludedKeyNames, true)
                || in_array($keyName, $appends, true)
                || new Relation(modelPhpClass: $phpClass, name: $keyName)->getKind() !== null
            ) {
                continue;
            }

            $shape[$keyName] = (clone $attributeType)->setRequired(true);
        }

        return new ArrayType(shape: $shape);
    }


    /**
     * Resolves an attribute like the property read `$model->$key`: the model's
     * own attribute types (columns, casts, accessors, relations) first, then
     * attributes set earlier on this variable.
     */
    private function resolveAttributeType(MethodCallContext $call, ObjectType $modelType, string $key): ?Type
    {
        $className = $modelType->className;

        if ($className === null || str_contains($key, '->')) {
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
            return new ArrayType(shape: $this->normalizeSerializedModelProperties($call->scope, $modelType));
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

        $model = $modelType->className ? ModelResolver::resolve($modelType->className) : null;
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
}

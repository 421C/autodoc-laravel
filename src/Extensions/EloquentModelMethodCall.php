<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Extensions;

use AutoDoc\Analyzer\MethodCallContext;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Extensions\MethodCallExtension;
use AutoDoc\Laravel\Helpers\ChecksModelAttributeVisibility;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\Variable;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * Handles method calls on `Illuminate\Database\Eloquent\Model` class.
 */
class EloquentModelMethodCall extends MethodCallExtension
{
    use ChecksModelAttributeVisibility;

    public function handleSideEffect(MethodCallContext $call): void
    {
        if ($call->methodName === 'setAttribute') {
            $this->handleSetAttribute($call);
        }
    }


    private function handleSetAttribute(MethodCallContext $call): void
    {
        $modelType = $this->getModelType($call);

        if (! $modelType) {
            return;
        }

        $attribute = $this->parseSetAttributeCall($call, $modelType);
        $var = $call->node->var;

        if ($attribute !== null && $var instanceof Variable && is_string($var->name)) {
            [$name, $type] = $attribute;

            $call->mutateVar($var->name, [$name => clone $type]);
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

        if (! $modelType) {
            return null;
        }

        return match ($call->methodName) {
            'setAttribute' => $this->getSetAttributeReturnType($call, $modelType),
            'getAttribute' => $this->getGetAttributeReturnType($call, $modelType),
            'attributesToArray' => $this->resolveAttributesArrayType($call, $modelType),
            default => $this->getToArrayReturnType($call, $modelType),
        };
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
                if (! isset($modelArrayRepresentation->shape) && ! isset($modelArrayRepresentation->itemType)) {
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
        if ($modelType->properties !== []) {
            return new ArrayType(shape: $modelType->properties);
        }

        if ($modelType->className === null) {
            return null;
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

        $model = $this->makeModel($modelType);

        if ($model) {
            if ($this->isModelAttributeHidden($model, $key)) {
                return null;
            }

            if ($this->isAttributeValueTransformed($model, $key)) {
                // The assigned value changes on write (cast, date, or set
                // mutator): keep the resolved attribute type when the model
                // has one, otherwise record the attribute without a type.
                return isset($modelType->properties[$key]) || isset($modelType->hiddenProperties[$key])
                    ? null
                    : [$key, (new UnknownType)->setRequired(true)];
            }
        }

        $valueType = clone $call->argTypes->get($valueIndex)->unwrapType($call->scope->config);

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


    private function makeModel(ObjectType $modelType): ?Model
    {
        if (! $modelType->className) {
            return null;
        }

        try {
            $model = app()->make($modelType->className);

        } catch (Throwable) {
            return null;
        }

        return $model instanceof Model ? $model : null;
    }


    /**
     * Whether Laravel transforms values assigned to this attribute in
     * `setAttribute()`: casts, date attributes, and set mutators (classic
     * `set{Studly}Attribute` or `Attribute`-style).
     */
    private function isAttributeValueTransformed(Model $model, string $key): bool
    {
        if ($model->hasCast($key) || in_array($key, $model->getDates(), true)) {
            return true;
        }

        if (method_exists($model, 'set' . Str::studly($key) . 'Attribute')) {
            return true;
        }

        $accessorName = Str::camel($key);

        if (! method_exists($model, $accessorName)) {
            return false;
        }

        $returnType = new ReflectionMethod($model, $accessorName)->getReturnType();

        return $returnType instanceof ReflectionNamedType && $returnType->getName() === Attribute::class;
    }


    private function getModelType(MethodCallContext $call): ?ObjectType
    {
        $varType = $call->getVarType();

        if (! ($varType instanceof ObjectType)
            || ! $varType->className
            || ! is_subclass_of($varType->className, Model::class)
        ) {
            return null;
        }

        return $varType;
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Validation;

use AutoDoc\Analyzer\PhpEnum;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\BoolType;
use AutoDoc\DataTypes\FloatType;
use AutoDoc\DataTypes\IntegerType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\NumberType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\DataTypes\UnresolvedPhpDocType;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;
use PhpParser\Node;


trait ValidationRulesParser
{
    /**
     * @param array<string, Type> $validationRules
     */
    protected function parseValidationRules(array $validationRules): ObjectType
    {
        $structured = [];

        foreach ($validationRules as $key => $value) {
            $segments = preg_split('/(?<!\\\\)\./', $key);
            $segments = array_map(fn ($s) => str_replace('\\.', '.', $s), $segments ?: []);

            $parsedValue = $this->parseTypeContainingValidationRules($value);
            $this->buildTypeStructure($structured, $segments, $parsedValue);
        }

        return new ObjectType($structured);
    }

    /**
     * @param array<string, Type> $structure
     * @param array<int, string> $segments
     */
    protected function buildTypeStructure(array &$structure, array $segments, Type $type): void
    {
        if (empty($segments)) {
            return;
        }

        $segment = array_shift($segments);

        if (empty($segments)) {
            $structure[$segment] = $type;

            return;
        }

        if ($segment === '*') {
            throw new \LogicException('Wildcard (*) cannot be the first segment in a path');
        }

        if ($segments[0] === '*') {
            if (! isset($structure[$segment])) {
                $structure[$segment] = new ArrayType(itemType: new ArrayType(shape: []));

            } else if (! ($structure[$segment] instanceof ArrayType)) {
                $structure[$segment] = new ArrayType(itemType: new ArrayType(shape: []));

            } else if ($structure[$segment]->itemType === null) {
                $structure[$segment]->itemType = new ArrayType(shape: []);

            } else if (!($structure[$segment]->itemType instanceof ArrayType)) {
                $structure[$segment]->itemType = new ArrayType(shape: []);

            } else if (! $structure[$segment]->itemType->shape) {
                $structure[$segment]->itemType = new ArrayType(shape: []);
            }

            $itemShape = &$structure[$segment]->itemType->shape;

            array_shift($segments);

            $this->buildTypeStructure($itemShape, $segments, $type);

        } else {
            if (! isset($structure[$segment])) {
                $structure[$segment] = new ArrayType(shape: []);

            } else if (!($structure[$segment] instanceof ArrayType)) {
                $structure[$segment] = new ArrayType(shape: []);

            } else if (! $structure[$segment]->shape) {
                $structure[$segment] = new ArrayType(shape: []);
            }

            /** @phpstan-ignore argument.type */
            $this->buildTypeStructure($structure[$segment]->shape, $segments, $type);
        }
    }

    protected function parseTypeContainingValidationRules(?Type $value): Type
    {
        $paramType = new UnknownType;

        if ($value instanceof Type) {
            /**
             * If parameter type specified with `@var` use it.
             * Otherwise extract laravel validation rules to determine parameter type.
             */
            if ($value instanceof UnresolvedPhpDocType) {
                $paramTypeFromValidationRules = $this->parseTypeContainingValidationRules($value->fallbackType);

                $value->required = $paramTypeFromValidationRules->required;

                return $value;
            }

            $originalValueType = $value;
            $value = $originalValueType->unwrapType();

            if ($value instanceof StringType) {
                if (is_string($value->value)) {
                    $paramType = $this->getTypeFromRules(explode('|', $value->value));
                }

            } else if ($value instanceof ArrayType && $value->itemType) {
                $resolvedItemType = $value->itemType->unwrapType();

                if ($resolvedItemType instanceof UnionType) {
                    $typesInArray = $resolvedItemType->types;

                } else {
                    $typesInArray = [$value->itemType];
                }

                $rules = [];

                foreach ($typesInArray as $typeInArray) {
                    $resolvedType = $typeInArray->unwrapType();

                    if ($resolvedType instanceof StringType) {
                        if (is_string($resolvedType->value)) {
                            $rules[] = $resolvedType->value;
                        }

                    } else if ($resolvedType instanceof ObjectType) {
                        if ($resolvedType->className) {
                            $rules[] = new Rule($resolvedType->className, $resolvedType->constructorArgs);
                        }
                    }
                }

                $paramType = $this->getTypeFromRules($rules);

            } else if ($value instanceof ObjectType) {
                if ($value->className) {
                    $paramType = $this->getTypeFromRules([
                        new Rule($value->className, $value->constructorArgs),
                    ]);
                }
            }

            $paramType->description = $paramType->description ?: $value->description;
            $paramType->examples = $paramType->examples ?: $value->examples;
        }

        return $paramType;
    }


    /**
     * @param array<string|Rule> $rules
     */
    protected function getTypeFromRules(array $rules): Type
    {
        $type = new UnknownType;

        foreach ($rules as $rule) {
            if ($rule instanceof Rule) {
                $typeOrNull = match ($rule->className) {
                    ArrayRule::class => new ArrayType,
                    default => null,
                };

                if ($typeOrNull) {
                    $type = $typeOrNull;
                    break;
                }

            } else {
                $rule = explode(':', $rule, 2)[0];

                $typeOrNull = match ($rule) {
                    'array'     => new ArrayType,
                    'boolean'   => new BoolType,
                    'confirmed',
                    'current_password' => new StringType(format: 'password'),
                    'date'      => new StringType(format: 'date'),
                    'email'     => new StringType(format: 'email'),
                    'integer'   => new IntegerType,
                    'ipv4'      => new StringType(format: 'ipv4'),
                    'ipv6'      => new StringType(format: 'ipv6'),
                    'numeric'   => new NumberType,
                    'object'    => new ObjectType,
                    'uuid'      => new StringType(format: 'uuid'),
                    'url'       => new StringType(format: 'uri'),
                    'string'    => new StringType,
                    default     => null,
                };

                if ($typeOrNull) {
                    $type = $typeOrNull;
                    break;
                }
            }
        }

        if ($type instanceof IntegerType || $type instanceof StringType || $type instanceof UnknownType) {
            foreach ($rules as $rule) {
                if ($rule instanceof Rule) {
                    if ($rule->className === In::class) {
                        $firstArg = $rule->args[0]->node ?? null;

                        if ($firstArg instanceof Node\Arg
                            && $firstArg->value instanceof Node\Expr\Array_
                        ) {
                            $scope = $rule->args[0]->scope;
                            $arrayType = $scope->resolveType($firstArg->value);

                            if ($arrayType instanceof ArrayType && $arrayType->itemType) {
                                if ($arrayType->itemType instanceof UnionType) {
                                    $allowedValueTypes = $arrayType->itemType->types;

                                } else {
                                    $allowedValueTypes = [$arrayType->itemType];
                                }

                                $isStringType = false;
                                $isIntType = false;
                                $isFloatType = false;
                                $enumValues = [];

                                foreach ($allowedValueTypes as $allowedType) {
                                    $allowedType = $allowedType->unwrapType();

                                    if ($allowedType instanceof StringType && is_string($allowedType->value)) {
                                        $isStringType = true;

                                    } else if ($allowedType instanceof IntegerType && is_int($allowedType->value)) {
                                        $isIntType = true;

                                    } else if ($allowedType instanceof FloatType && is_float($allowedType->value)) {
                                        $isFloatType = true;

                                    } else {
                                        $enumValues = [];
                                        break;
                                    }

                                    $enumValues[] = $allowedType->value;
                                }

                                if ($enumValues) {
                                    if ($type instanceof IntegerType) {
                                        $type->setEnumValues(array_map(intval(...), $enumValues));

                                    } else if ($isStringType) {
                                        /** @var array<string> $enumValues */
                                        $type = new StringType($enumValues);

                                    } else if ($isIntType && $isFloatType) {
                                        /** @var array<int|float> $enumValues */
                                        $type = new NumberType($enumValues);

                                    } else if ($isIntType) {
                                        /** @var array<int> $enumValues */
                                        $type = new IntegerType($enumValues);

                                    } else if ($isFloatType) {
                                        /** @var array<float> $enumValues */
                                        $type = new FloatType($enumValues);

                                    } else {
                                        /** @var array<string> $enumValues */
                                        $type = new StringType($enumValues);
                                    }

                                } else {
                                    $type = new UnknownType;
                                }
                            }
                        }
                    }

                } else {
                    if (str_starts_with($rule, 'in:')) {
                        $enumValues = explode(',', substr($rule, strlen('in:')));

                        if ($type instanceof IntegerType) {
                            $type->setEnumValues(array_map(intval(...), $enumValues));

                        } else {
                            $type = new StringType($enumValues);
                        }
                    }
                }
            }
        }

        foreach ($rules as $rule) {
            if ($rule instanceof Rule) {
                if ($rule->className === Enum::class) {
                    $arg = $rule->args[0] ?? null;

                    if ($arg && $arg->node instanceof Node\Arg) {
                        $enumClassType = $arg->scope->resolveType($arg->node->value);

                        if ($enumClassType instanceof StringType && is_string($enumClassType->value) && enum_exists($enumClassType->value)) {
                            $enumClass = $arg->scope->getPhpClassInDeeperScope($enumClassType->value);

                            $type = (new PhpEnum($enumClass))->resolveType();
                        }
                    }
                }
            }
        }

        if ($type instanceof IntegerType) {
            foreach ($rules as $rule) {
                if (is_string($rule)) {
                    if (str_starts_with($rule, 'min:')) {
                        $type->minimum = (int) explode(':', $rule)[1];

                    } else if (str_starts_with($rule, 'max:')) {
                        $type->maximum = (int) explode(':', $rule)[1];
                    }
                }
            }
        }

        if (in_array('nullable', $rules)) {
            $type = new UnionType([$type, new NullType]);
        }

        if (in_array('required', $rules)) {
            $type->required = true;
        }

        return $type;
    }
}

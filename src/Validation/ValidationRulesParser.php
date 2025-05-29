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
use AutoDoc\DataTypes\VoidType;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Password;
use PhpParser\Node;


trait ValidationRulesParser
{
    /**
     * @param array<string, Type> $validationRules
     */
    protected function parseValidationRules(array $validationRules): ObjectType|ArrayType
    {
        $structured = [];
        $hasWildcardRoot = false;

        foreach ($validationRules as $key => $value) {
            $segments = preg_split('/(?<!\\\\)\./', $key);
            $segments = array_map(fn ($s) => str_replace('\\.', '.', $s), $segments ?: []);

            if (isset($segments[0]) && $segments[0] === '*') {
                $hasWildcardRoot = true;
            }

            $validationType = $this->parseTypeContainingValidationRules($value);

            if ($validationType instanceof ConfirmedType) {
                $this->buildTypeStructure($structured, $segments, $validationType->type);

                /** @var int */
                $lastSegmentIndex = array_key_last($segments);
                $typeClass = get_class($validationType->type);

                $confirmationType = new $typeClass;
                $confirmationType->required = $validationType->type->required;

                if (config('autodoc.laravel.generate_descriptions_from_validation_rules')) {
                    /** @var ?string */
                    $format = config('autodoc.laravel.format_generated_descriptions');

                    $description = 'Must match "' . $segments[$lastSegmentIndex] . '".';

                    $confirmationType->description = $format ? sprintf($format, $description) : $description;
                }

                if ($confirmationType instanceof StringType) {
                    $confirmationType->format = $validationType->type->format ?? null;
                }

                $segments[$lastSegmentIndex] = $validationType->confirmationKey ?? ($segments[$lastSegmentIndex] . '_confirmation');

                $this->buildTypeStructure($structured, $segments, $confirmationType);

            } else {
                $this->buildTypeStructure($structured, $segments, $validationType);
            }
        }

        if ($hasWildcardRoot) {
            $resultType = new ArrayType(itemType: $structured['*']);

        } else {
            $resultType = new ObjectType($structured);
        }

        return $this->checkRequiredContainerTypes($resultType);
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

        if ($segments[0] === '*') {
            if (! isset($structure[$segment])) {
                $structure[$segment] = new ArrayType(itemType: new ArrayType(shape: []));

            } else if (! ($structure[$segment] instanceof ArrayType)) {
                $structure[$segment] = (new ArrayType(itemType: new ArrayType(shape: [])))->setRequired($structure[$segment]->required);

            } else if ($structure[$segment]->itemType === null) {
                $structure[$segment]->itemType = new ArrayType(shape: []);

            } else if (!($structure[$segment]->itemType instanceof ArrayType)) {
                $structure[$segment]->itemType = (new ArrayType(shape: []))->setRequired($structure[$segment]->itemType->required);

            } else if (! $structure[$segment]->itemType->shape) {
                $structure[$segment]->itemType = (new ArrayType(shape: []))->setRequired($structure[$segment]->itemType->required);
            }

            $itemShape = &$structure[$segment]->itemType->shape;

            array_shift($segments);

            if (empty($segments)) {
                $structure[$segment]->itemType = $type;

                return;
            }

            $this->buildTypeStructure($itemShape, $segments, $type);

        } else {
            if (! isset($structure[$segment])) {
                $structure[$segment] = new ArrayType(shape: []);

            } else if (!($structure[$segment] instanceof ArrayType)) {
                $structure[$segment] = (new ArrayType(shape: []))->setRequired($structure[$segment]->required);

            } else if (! $structure[$segment]->shape) {
                $structure[$segment] = (new ArrayType(shape: []))->setRequired($structure[$segment]->required);
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
                $typeFromLaravelValidation = $this->parseTypeContainingValidationRules($value->fallbackType);

                $value->required = $typeFromLaravelValidation->required;

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

                    if ($resolvedType instanceof UnionType) {
                        $types = array_filter($resolvedType->types, fn ($type) => ! ($type instanceof VoidType));

                        if (count($types) === 1) {
                            $resolvedType = reset($types)->unwrapType();
                        }
                    }

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
                    Email::class => new StringType(format: 'email'),
                    Password::class => new StringType(format: 'password'),
                    default => null,
                };

                if ($typeOrNull) {
                    $type = $typeOrNull;
                    break;
                }

            } else {
                [$rule, $params] = explode(':', $rule, 2) + [1 => null];

                $typeOrNull = match ($rule) {
                    'array'     => new ArrayType,
                    'boolean'   => new BoolType,
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
                    default     => null,
                };

                if ($typeOrNull) {
                    $type = $typeOrNull;
                    break;
                }
            }
        }

        if ($type instanceof UnknownType && in_array('string', $rules)) {
            $type = new StringType;
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

                                    $type->isEnum = true;

                                } else {
                                    $type = new UnknownType;
                                }
                            }
                        }
                    }

                } else if (str_starts_with($rule, 'in:')) {
                    $enumValues = explode(',', substr($rule, strlen('in:')));

                    if ($type instanceof IntegerType) {
                        $type->setEnumValues(array_map(intval(...), $enumValues));

                    } else {
                        if (! ($type instanceof StringType)) {
                            $type = new StringType;
                        }

                        $type->setEnumValues($enumValues);
                    }
                }
            }
        }

        foreach ($rules as $rule) {
            if ($rule instanceof Rule) {
                if ($rule->className === Enum::class) {
                    $arg = $rule->args[0] ?? null;

                    if ($arg && $arg->node instanceof Node\Arg) {
                        $enumClassNameType = $arg->scope->resolveType($arg->node->value);

                        if ($enumClassNameType instanceof StringType && is_string($enumClassNameType->value) && enum_exists($enumClassNameType->value)) {
                            $enumClass = $arg->scope->getPhpClassInDeeperScope($enumClassNameType->value);

                            $type = (new PhpEnum($enumClass))->resolveType();
                        }
                    }
                }
            }
        }

        foreach ($rules as $rule) {
            if (is_string($rule)) {
                if (str_starts_with($rule, 'required_array_keys:')) {
                    $keys = explode(',', explode(':', $rule, 2)[1]);

                    if (! ($type instanceof ArrayType)) {
                        $type = new ArrayType;
                    }

                    foreach ($keys as $key) {
                        if (! isset($type->shape[$key])) {
                            $type->shape[$key] = new UnknownType;
                        }

                        $type->shape[$key]->required = true;
                    }
                }
            }
        }

        if ($type instanceof IntegerType) {
            $type->allowTrueAsInteger = ! in_array('numeric', $rules);
            $type->isString = in_array('string', $rules);
            $type->isStrictInteger = in_array('integer', $rules);
        }

        if ($type instanceof NumberType) {
            if (in_array('integer', $rules)) {
                $type = new IntegerType;
                $type->isStrictInteger = true;
            }

            $type->isString = in_array('string', $rules);
        }

        foreach ($rules as $rule) {
            if (is_string($rule)) {
                if (str_starts_with($rule, 'min:')) {
                    $param = (int) explode(':', $rule, 2)[1];

                    if ($type instanceof IntegerType) {
                        $type->minimum = $param;

                    } else if ($type instanceof ArrayType) {
                        $type->minItems = $param;

                    } else {
                        if (! ($type instanceof StringType)) {
                            $type = new StringType;
                        }

                        $type->minLength = $param;
                    }

                } else if (str_starts_with($rule, 'max:')) {
                    $param = (int) explode(':', $rule, 2)[1];

                    if ($type instanceof IntegerType) {
                        $type->maximum = $param;

                    } else if ($type instanceof ArrayType) {
                        $type->maxItems = $param;

                    } else {
                        if (! ($type instanceof StringType)) {
                            $type = new StringType;
                        }

                        $type->maxLength = $param;
                    }
                }
            }
        }

        if (in_array('nullable', $rules)) {
            $type = new UnionType([$type, new NullType]);
        }

        if (in_array('required', $rules) || in_array('present', $rules)) {
            $type->required = true;
        }

        if (config('autodoc.laravel.generate_descriptions_from_validation_rules')) {
            foreach ($rules as $rule) {
                if (! is_string($rule)) {
                    continue;
                }

                if (str_starts_with($rule, 'required_if:')
                    || str_starts_with($rule, 'required_unless:')
                    || str_starts_with($rule, 'required_if_accepted:')
                    || str_starts_with($rule, 'required_if_declined:')
                    || str_starts_with($rule, 'required_without:')
                ) {
                    [$ruleKey, $field] = explode(':', $rule, 2);

                    if (! $field) {
                        continue;
                    }

                    $description = null;

                    if ($ruleKey === 'required_if_accepted') {
                        $value = 'true';

                    } else if ($ruleKey === 'required_if_declined') {
                        $value = 'false';

                    } else {
                        [$field, $value] = explode(',', $field, 2) + [1 => null];
                    }

                    if ($value !== null) {
                        $keyword = $ruleKey === 'required_unless' ? 'unless' : 'if';

                        if ($value === '') {
                            $description = "Required $keyword \"$field\" is empty.";

                        } else if (in_array($value, ['true', 'false', 'null'])) {
                            $description = "Required $keyword \"$field\" is $value.";

                        } else {
                            $description = "Required $keyword \"$field\" is \"$value\".";
                        }
                    }

                    if ($description) {
                        /** @var ?string */
                        $format = config('autodoc.laravel.format_generated_descriptions');

                        $description = $format ? sprintf($format, $description) : $description;
                        $type->description = $type->description ? $type->description . "\n\n" . $description : $description;
                    }
                }

                if (str_starts_with($rule, 'required_with:')
                    || str_starts_with($rule, 'required_without:')
                    || str_starts_with($rule, 'required_with_all:')
                    || str_starts_with($rule, 'required_without_all:')
                ) {
                    $keys = explode(':', $rule);
                    $ruleKey = array_shift($keys);

                    if ($keys) {
                        $description = null;

                        if (count($keys) === 1) {
                            $key = '"' . $keys[0] . '"';

                            if ($ruleKey === 'required_with') {
                                $description = "Required if $key is not empty.";

                            } else if ($ruleKey === 'required_with_all') {
                                $description = "Required if $key is not empty.";

                            } else if ($ruleKey === 'required_without') {
                                $description = "Required if $key is empty.";

                            } else if ($ruleKey === 'required_without_all') {
                                $description = "Required if $key is empty.";
                            }

                        } else {
                            $keys = '"' . implode('", "', $keys) . '"';

                            if ($ruleKey === 'required_with') {
                                $description = "Required if any of the following fields are filled: $keys";

                            } else if ($ruleKey === 'required_with_all') {
                                $description = "Required if none of the following fields are empty: $keys";

                            } else if ($ruleKey === 'required_without') {
                                $description = "Required if any of the following fields are empty: $keys";

                            } else if ($ruleKey === 'required_without_all') {
                                $description = "Required if all of the following fields are empty: $keys";
                            }
                        }

                        if ($description) {
                            /** @var ?string */
                            $format = config('autodoc.laravel.format_generated_descriptions');

                            $description = $format ? sprintf($format, $description) : $description;
                            $type->description = $type->description ? $type->description . "\n\n" . $description : $description;
                        }
                    }
                }
            }
        }

        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            if ($rule === 'confirmed' || str_starts_with($rule, 'confirmed:')) {
                $confirmationKey = explode(':', $rule, 2)[1] ?? null;

                $type = new ConfirmedType($confirmationKey, $type);
            }
        }

        return $type;
    }

    /**
     * @template T of Type
     * @param T $type
     * @return T
     */
    protected function checkRequiredContainerTypes(Type $type): Type
    {
        if ($type->required) {
            return $type;
        }

        if ($type instanceof ArrayType) {
            if ($type->shape) {
                foreach ($type->shape as $key => $propType) {
                    $type->shape[$key] = $this->checkRequiredContainerTypes($propType);

                    if ($propType->required) {
                        $type->required = true;
                    }
                }
            }

        } else if ($type instanceof ObjectType) {
            foreach ($type->properties as $key => $propType) {
                $type->properties[$key] = $this->checkRequiredContainerTypes($propType);

                if ($propType->required) {
                    $type->required = true;
                }
            }
        }

        return $type;
    }
}

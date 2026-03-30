<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;

trait DotNotationParser
{
    /**
     * @param array<string, Type> $structure
     * @param array<int, string> $segments
     */
    protected function dotNotationToNestedArrayType(array &$structure, array $segments, Type $type): void
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
            $nullable = false;

            if (! isset($structure[$segment])) {
                $structure[$segment] = new ArrayType(itemType: new ArrayType(shape: []));

            } else if (! ($structure[$segment] instanceof ArrayType)) {
                $existing = $structure[$segment];

                if ($existing instanceof UnionType) {
                    $innerArray = null;

                    foreach ($existing->types as $innerType) {
                        if ($innerType instanceof ArrayType) {
                            $innerArray = $innerType;

                        } else if ($innerType instanceof NullType) {
                            $nullable = true;
                        }
                    }

                    if ($innerArray !== null) {
                        if ($innerArray->itemType === null) {
                            $innerArray->itemType = new ArrayType(shape: []);

                        } else if (! ($innerArray->itemType instanceof ArrayType)) {
                            $innerArray->itemType = (new ArrayType(shape: []))->setRequired($innerArray->itemType->required);

                        } else if (! $innerArray->itemType->shape) {
                            $innerArray->itemType = (new ArrayType(shape: []))->setRequired($innerArray->itemType->required);
                        }

                        $structure[$segment] = $innerArray;

                    } else {
                        $structure[$segment] = (new ArrayType(itemType: new ArrayType(shape: [])))->setRequired($existing->required);
                    }

                } else {
                    $structure[$segment] = (new ArrayType(itemType: new ArrayType(shape: [])))->setRequired($existing->required);
                }

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

                if ($nullable) {
                    $structure[$segment] = new UnionType([$structure[$segment], new NullType]);
                }

                return;
            }

            $this->dotNotationToNestedArrayType($itemShape, $segments, $type);

            if ($nullable) {
                $structure[$segment] = new UnionType([$structure[$segment], new NullType]);
            }

        } else {
            if (! isset($structure[$segment])) {
                $structure[$segment] = new ArrayType(shape: []);

            } else if (! ($structure[$segment] instanceof ArrayType)) {
                $structure[$segment] = (new ArrayType(shape: []))->setRequired($structure[$segment]->required);
            }

            /** @phpstan-ignore argument.type */
            $this->dotNotationToNestedArrayType($structure[$segment]->shape, $segments, $type);
        }
    }
}

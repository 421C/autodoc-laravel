<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\Type;

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

            $this->dotNotationToNestedArrayType($itemShape, $segments, $type);

        } else {
            if (! isset($structure[$segment])) {
                $structure[$segment] = new ArrayType(shape: []);

            } else if (!($structure[$segment] instanceof ArrayType)) {
                $structure[$segment] = (new ArrayType(shape: []))->setRequired($structure[$segment]->required);

            } else if (! $structure[$segment]->shape) {
                $structure[$segment] = (new ArrayType(shape: []))->setRequired($structure[$segment]->required);
            }

            /** @phpstan-ignore argument.type */
            $this->dotNotationToNestedArrayType($structure[$segment]->shape, $segments, $type);
        }
    }
}

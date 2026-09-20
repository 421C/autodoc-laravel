<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\ArgumentList;
use AutoDoc\Analyzer\PhpClass;
use AutoDoc\Analyzer\Scope;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\StringType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Laravel\Helpers\DotNotationParser;
use Illuminate\Database\Eloquent\Model;

/**
 * The relations named by `with(...)` / `load(...)` arguments, and the property
 * types they resolve to on a model of a given class.
 */
final class EagerLoad
{
    use DotNotationParser;

    public function __construct(
        private Scope $scope,
    ) {}

    /** @var array<string, Type> */
    private array $arguments = [];


    public function addArguments(ArgumentList $arguments): void
    {
        $argumentListArrayType = $this->scope->withPartialArraysResolvingAsShapes(function () use ($arguments) {
            if ($arguments->has(0)) {
                $firstArgType = $arguments->get(0)->unwrapType($this->scope->config);

                if ($firstArgType instanceof ArrayType) {
                    return $firstArgType;
                }
            }

            $shape = [];

            for ($index = 0; $index < count($arguments); $index++) {
                $shape[] = $arguments->get($index)->unwrapType($this->scope->config);
            }

            return new ArrayType(shape: $shape);
        });

        $this->normalizeArgumentArray($argumentListArrayType, $this->arguments);
    }


    public function isEmpty(): bool
    {
        return $this->arguments === [];
    }


    /**
     * @param class-string<Model> $modelClassName
     * @return array<string, Type>
     */
    public function resolveRelationTypes(string $modelClassName): array
    {
        if ($this->isEmpty()) {
            return [];
        }

        $modelPhpClass = $this->scope->getPhpClassInDeeperScope($modelClassName);
        $relations = [];

        foreach ($this->arguments as $key => $relationArgumentType) {
            $relation = $this->makeRelationObject($key, $relationArgumentType, $modelPhpClass);

            if (isset($relations[$relation->exportedName])) {
                $relations[$relation->exportedName]->columns = array_merge($relations[$relation->exportedName]->columns, $relation->columns);
                $relations[$relation->exportedName]->relations = array_merge($relations[$relation->exportedName]->relations, $relation->relations);

            } else {
                $relations[$relation->exportedName] = $relation;
            }
        }

        $relationTypes = [];

        foreach ($relations as $name => $relation) {
            $relationTypes[$name] = $relation->resolveType() ?? new UnknownType;
        }

        return $relationTypes;
    }


    /**
     * @param array<string, Type> &$normalizedShape
     */
    private function normalizeArgumentArray(ArrayType $arrayType, array &$normalizedShape): void
    {
        $shape = $arrayType->shape;

        if (! $shape && $arrayType->itemType) {
            $shape = $arrayType->itemType instanceof UnionType
                ? $arrayType->itemType->types
                : [$arrayType->itemType];
        }

        foreach ($shape as $key => $valueType) {
            $valueType = $valueType->unwrapType($this->scope->config);

            if (is_string($key)) {
                $keyVariants = [$key];

                if ($valueType instanceof ArrayType) {
                    $relationArgumentShape = [];

                    $this->normalizeArgumentArray($valueType, $relationArgumentShape);

                    $valueType = new ArrayType(shape: $relationArgumentShape);
                }

            } else {
                $keyVariants = [];

                if ($valueType instanceof StringType) {
                    $keyVariants = $valueType->getPossibleValues() ?? [];
                    $valueType = new UnknownType;
                }
            }

            foreach ($keyVariants as $dotNotationString) {
                $this->dotNotationToNestedArrayType($normalizedShape, $this->splitDotNotation($dotNotationString), $valueType);
            }
        }
    }


    /**
     * @param PhpClass<Model> $modelPhpClass
     */
    private function makeRelationObject(string $key, Type $relationArgumentType, PhpClass $modelPhpClass): Relation
    {
        $parts = explode(':', $key, 2);

        $relation = new Relation(
            modelPhpClass: $modelPhpClass,
            name: $parts[0],
            columns: isset($parts[1]) ? explode(',', $parts[1]) : [],
            relations: [],
        );

        $relationArgumentType = $this->scope->withPartialArraysResolvingAsShapes(
            fn () => $relationArgumentType->unwrapType($this->scope->config)
        );

        if (! ($relationArgumentType instanceof ArrayType)) {
            return $relation;
        }

        $relatedModelClassName = $relation->getRelatedModelClassName();

        if (! $relatedModelClassName) {
            return $relation;
        }

        $relatedModelPhpClass = $modelPhpClass->scope->getPhpClassInDeeperScope($relatedModelClassName);

        if ($relationArgumentType->shape) {
            foreach ($relationArgumentType->shape as $subRelationKey => $valueType) {
                $subRelation = $this->makeRelationObject((string) $subRelationKey, $valueType, $relatedModelPhpClass);

                $relation->relations[$subRelation->exportedName] = $subRelation;
            }

        } else if ($relationArgumentType->itemType instanceof StringType) {
            foreach ($relationArgumentType->itemType->getPossibleValues() ?? [] as $subRelationKey) {
                $subRelation = $this->makeRelationObject($subRelationKey, new UnknownType, $relatedModelPhpClass);

                $relation->relations[$subRelation->exportedName] = $subRelation;
            }
        }

        return $relation;
    }
}

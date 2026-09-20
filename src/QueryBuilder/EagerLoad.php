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
use AutoDoc\Laravel\Helpers\ModelResolver;
use AutoDoc\Laravel\Helpers\ParsesKeyListArguments;
use Illuminate\Database\Eloquent\Model;

/**
 * The relations named by `with(...)` / `load(...)` arguments, and the property
 * types they resolve to on a model of a given class.
 */
final class EagerLoad
{
    use DotNotationParser, ParsesKeyListArguments;

    public function __construct(
        private Scope $scope,
    ) {}

    /** @var array<string, Type> */
    private array $arguments = [];


    public function addRelationArgument(ArgumentList $arguments): void
    {
        $index = $arguments->indexForParameter('relation', 0);

        if ($index === null) {
            return;
        }

        $relationArgument = $arguments->get($index);

        $this->normalizeArgumentArray(
            $this->scope->withPartialArraysResolvingAsShapes(
                fn () => new ArrayType(shape: [$relationArgument->unwrapType($this->scope->config)]),
            ),
            $this->arguments,
        );
    }


    public function replaceArguments(ArgumentList $arguments): void
    {
        $this->arguments = [];

        $this->addArguments($arguments);
    }


    public function removeArguments(ArgumentList $arguments): void
    {
        $removedNames = $this->resolveKeyListNames($arguments, $this->scope->config, allowVariadic: true);

        foreach (array_keys($this->arguments) as $relationArgument) {
            [$relationName] = self::splitRelationColumns($relationArgument);

            if (in_array($relationName, $removedNames, strict: true)) {
                unset($this->arguments[$relationArgument]);
            }
        }
    }


    /**
     * @return array{string, list<string>}
     */
    public static function splitRelationColumns(string $relationArgument): array
    {
        $parts = explode(':', $relationArgument, 2);

        return [$parts[0], isset($parts[1]) ? explode(',', $parts[1]) : []];
    }


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


    /**
     * @param list<string> $relationNames
     */
    public function addRelationNames(array $relationNames): void
    {
        foreach ($relationNames as $relationName) {
            $this->dotNotationToNestedArrayType(
                $this->arguments,
                $this->splitRelationPath($relationName),
                new UnknownType,
            );
        }
    }


    /**
     * @param class-string<Model> $modelClassName
     * @return array<string, Type>
     */
    public static function defaultRelationTypes(Scope $scope, string $modelClassName): array
    {
        $eagerLoad = new self($scope);

        $eagerLoad->addRelationNames(ModelResolver::defaultEagerLoads($modelClassName));

        return $eagerLoad->resolveRelationTypes($modelClassName);
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

            foreach ($keyVariants as $relationArgument) {
                $this->dotNotationToNestedArrayType($normalizedShape, $this->splitRelationPath($relationArgument), $valueType);
            }
        }
    }


    /**
     * @return list<string>
     */
    private function splitRelationPath(string $relationArgument): array
    {
        $parts = explode(':', $relationArgument, 2);
        $segments = $this->splitDotNotation($parts[0]);

        if (! isset($parts[1])) {
            return $segments;
        }

        $relationName = (string) array_pop($segments);

        $segments[] = $relationName . ':' . $parts[1];

        return $segments;
    }


    /**
     * @param PhpClass<Model> $modelPhpClass
     */
    private function makeRelationObject(string $key, Type $relationArgumentType, PhpClass $modelPhpClass): Relation
    {
        [$relationName, $columns] = self::splitRelationColumns($key);

        $relation = new Relation(
            modelPhpClass: $modelPhpClass,
            name: $relationName,
            columns: $columns,
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

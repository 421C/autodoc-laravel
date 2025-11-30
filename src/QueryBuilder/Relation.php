<?php declare(strict_types=1);

namespace AutoDoc\Laravel\QueryBuilder;

use AutoDoc\Analyzer\PhpClass;
use AutoDoc\DataTypes\ArrayType;
use AutoDoc\DataTypes\NullType;
use AutoDoc\DataTypes\ObjectType;
use AutoDoc\DataTypes\Type;
use AutoDoc\DataTypes\UnionType;
use AutoDoc\DataTypes\UnknownType;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Collection;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

class Relation
{
    public function __construct(
        /** @var PhpClass<Model> */
        private PhpClass $modelPhpClass,
        public string $name,

        /** @var string[] */
        public array $columns = [],

        /** @var array<string, Relation> */
        public array $relations = [],
    ) {}


    /** @var ?class-string<Model> */
    private ?string $relatedModelClassName = null;

    /** @var ?class-string */
    private ?string $relationTypeClassName = null;


    /**
     * @return ?class-string<Model>
     */
    public function getRelatedModelClassName(): ?string
    {
        if (! $this->relatedModelClassName) {
            $this->parseRelationDefinition();
        }

        return $this->relatedModelClassName;
    }

    /**
     * @return ?class-string
     */
    public function getRelationTypeClassName(): ?string
    {
        if (! $this->relationTypeClassName) {
            $this->parseRelationDefinition();
        }

        return $this->relationTypeClassName;
    }


    private function parseRelationDefinition(): void
    {
        if ($this->modelPhpClass->getReflection()->hasMethod($this->name)) {
            $phpDocReturnType = $this->modelPhpClass->getMethod($this->name)->getPhpFunction()?->getTypeFromPhpDocReturnTag();

            if ($phpDocReturnType && $phpDocReturnType->typeNode instanceof GenericTypeNode) {
                $this->relationTypeClassName = $this->modelPhpClass->scope->getResolvedClassName($phpDocReturnType->typeNode->type->name);

                if (isset($phpDocReturnType->typeNode->genericTypes[0])
                    && $phpDocReturnType->typeNode->genericTypes[0] instanceof IdentifierTypeNode
                ) {
                    $firstGenericTypeName = $phpDocReturnType->typeNode->genericTypes[0]->name;

                    if ($this->relationTypeClassName === HasOne::class
                        || $this->relationTypeClassName === BelongsTo::class
                        || $this->relationTypeClassName === HasOneThrough::class
                        || $this->relationTypeClassName === HasMany::class
                        || $this->relationTypeClassName === BelongsToMany::class
                        || $this->relationTypeClassName === HasManyThrough::class
                    ) {
                        $argumentClassName = $this->modelPhpClass->scope->getResolvedClassName($firstGenericTypeName);

                        if ($argumentClassName) {
                            if (is_subclass_of($argumentClassName, Model::class, true)) {
                                $this->relatedModelClassName = $argumentClassName;

                            } else {
                                if ($this->modelPhpClass->scope->isDebugModeEnabled()) {
                                    throw new Exception('Relation "' . $this->name . '" of "' . $this->modelPhpClass->className . '" is not related to an Eloquent Model');
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    public function resolveType(): ?Type
    {
        $relationTypeClassName = $this->getRelationTypeClassName();

        if ($relationTypeClassName === HasOne::class
            || $relationTypeClassName === BelongsTo::class
            || $relationTypeClassName === HasOneThrough::class
        ) {
            return new UnionType([
                $this->getRelatedModelObjectType(),
                new NullType,
            ]);

        } else if ($relationTypeClassName === HasMany::class
            || $relationTypeClassName === BelongsToMany::class
            || $relationTypeClassName === HasManyThrough::class
        ) {
            return new ArrayType(
                itemType: $this->getRelatedModelObjectType(),
                className: Collection::class,
            );
        }

        return null;
    }


    private function getRelatedModelObjectType(): ObjectType
    {
        $relatedModelClassName = $this->getRelatedModelClassName();

        if (! $relatedModelClassName) {
            return new ObjectType;
        }

        $objectType = clone $this->modelPhpClass->scope->getPhpClassInDeeperScope($relatedModelClassName)->resolveType();

        if ($this->columns) {
            $objectType->properties = array_filter($objectType->properties, fn ($propertyName) => in_array($propertyName, $this->columns), ARRAY_FILTER_USE_KEY);
        }

        foreach ($this->relations as $name => $relation) {
            $objectType->properties[$name] = $relation->resolveType() ?? new UnknownType;
        }

        return $objectType;
    }
}

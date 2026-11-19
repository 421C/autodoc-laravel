<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use Illuminate\Support\Arr;

class ArrHelperController
{
    public function __construct(
        private ?string $timezone,
        private string $keyName,
        /** @var list<int|null> */
        private array $ids,
        private bool $compact,
        /** @var list<'name'|'moons'> */
        private array $fields,
        private mixed $nickname,
        /** @var list<mixed> */
        private array $values,
        private int $index,
        /** @var array<string, array{id: int, name: string}> */
        private array $planetsByName,
        /** @var non-empty-list<int> */
        private array $moonCounts,
        /** @var list{int, ...<int|null>} */
        private array $rankedIds,
    ) {}


    /**
     * Arr filters
     */
    #[ExpectedOperationSchema([
        'summary' => 'Arr filters',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'whereNotNull' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'theme' => [
                                            'type' => 'string',
                                            'const' => 'dark',
                                        ],
                                        'timezone' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => [
                                        'theme',
                                    ],
                                ],
                                'whereNotNullList' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'integer',
                                            ],
                                        ],
                                        [
                                            'type' => 'object',
                                            'additionalProperties' => [
                                                'type' => 'integer',
                                            ],
                                        ],
                                    ],
                                ],
                                'whereNotNullMixed' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'theme' => [
                                            'type' => 'string',
                                            'const' => 'dark',
                                        ],
                                        'nickname' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => [
                                        'theme',
                                    ],
                                ],
                                'whereNotNullMixedList' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'string',
                                                'description' => 'mixed',
                                            ],
                                        ],
                                        [
                                            'type' => 'object',
                                            'additionalProperties' => [
                                                'type' => 'string',
                                                'description' => 'mixed',
                                            ],
                                        ],
                                    ],
                                ],
                                'where' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'moons' => [
                                            'type' => 'integer',
                                            'const' => 2,
                                        ],
                                    ],
                                ],
                                'whereNotNullOpenList' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'integer',
                                            ],
                                        ],
                                        [
                                            'type' => 'object',
                                            'properties' => [
                                                0 => [
                                                    'type' => 'integer',
                                                ],
                                            ],
                                            'required' => [
                                                '0',
                                            ],
                                            'additionalProperties' => [
                                                'type' => 'integer',
                                            ],
                                        ],
                                    ],
                                ],
                                'whereOpenList' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'integer',
                                            ],
                                        ],
                                        [
                                            'type' => 'object',
                                            'properties' => [
                                                0 => [
                                                    'type' => 'integer',
                                                ],
                                            ],
                                            'additionalProperties' => [
                                                'type' => 'integer',
                                            ],
                                        ],
                                    ],
                                ],
                                'whereOpenNever' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                    ],
                                ],
                            ],
                            'required' => [
                                'whereNotNull',
                                'whereNotNullList',
                                'whereNotNullMixed',
                                'whereNotNullMixedList',
                                'where',
                                'whereNotNullOpenList',
                                'whereOpenList',
                                'whereOpenNever',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function filters(): mixed
    {
        $settings = ['theme' => 'dark', 'locale' => null, 'timezone' => $this->timezone];

        return [
            'whereNotNull' => Arr::whereNotNull($settings),
            'whereNotNullList' => Arr::whereNotNull($this->ids),
            'whereNotNullMixed' => Arr::whereNotNull(['theme' => 'dark', 'nickname' => $this->nickname]),
            'whereNotNullMixedList' => Arr::whereNotNull($this->values),
            'where' => Arr::where(['moons' => 2, 'name' => 'Mars'], fn ($value) => is_int($value)),
            'whereNotNullOpenList' => Arr::whereNotNull($this->rankedIds),
            'whereOpenList' => Arr::where($this->rankedIds, fn ($value) => is_int($value)),
            'whereOpenNever' => Arr::where(['name' => 'Mars', ...$this->planetsByName], fn ($value) => is_string($value)),
        ];
    }


    /**
     * Arr whereNotNull spreads
     */
    #[ExpectedOperationSchema([
        'summary' => 'Arr whereNotNull spreads',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'spreadWithExtraKey' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'theme' => [
                                            'type' => 'string',
                                            'const' => 'dark',
                                        ],
                                        'timezone' => [
                                            'type' => 'string',
                                        ],
                                        'id' => [
                                            'type' => 'integer',
                                            'const' => 1,
                                        ],
                                    ],
                                    'required' => [
                                        'theme',
                                        'id',
                                    ],
                                ],
                                'overrideAfterSpread' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'theme' => [
                                            'type' => 'string',
                                            'const' => 'light',
                                        ],
                                        'timezone' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => [
                                        'theme',
                                    ],
                                ],
                                'filteredSpread' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'theme' => [
                                            'type' => 'string',
                                            'const' => 'dark',
                                        ],
                                        'locale' => [
                                            'type' => 'string',
                                        ],
                                        'timezone' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => [
                                        'theme',
                                    ],
                                ],
                                'spreadOfUnion' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'theme' => [
                                            'type' => 'string',
                                            'const' => 'dark',
                                        ],
                                        'timezone' => [
                                            'type' => 'string',
                                        ],
                                        'accent' => [
                                            'type' => 'string',
                                            'const' => 'red',
                                        ],
                                    ],
                                ],
                                'listSpread' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'integer',
                                    ],
                                ],
                            ],
                            'required' => [
                                'spreadWithExtraKey',
                                'overrideAfterSpread',
                                'filteredSpread',
                                'spreadOfUnion',
                                'listSpread',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function whereNotNullSpreads(): mixed
    {
        $settings = ['theme' => 'dark', 'locale' => null, 'timezone' => $this->timezone];

        return [
            'spreadWithExtraKey' => [...Arr::whereNotNull($settings), 'id' => 1],
            'overrideAfterSpread' => [...Arr::whereNotNull($settings), 'theme' => 'light'],
            'filteredSpread' => Arr::whereNotNull([...$settings, 'locale' => $this->timezone]),
            'spreadOfUnion' => [...Arr::whereNotNull($this->compact ? $settings : ['theme' => null, 'accent' => 'red'])],
            'listSpread' => [0, ...Arr::whereNotNull($this->ids)],
        ];
    }


    /**
     * Arr key subsets
     */
    #[ExpectedOperationSchema([
        'summary' => 'Arr key subsets',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'only' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                        'moons' => [
                                            'type' => 'integer',
                                            'const' => 2,
                                        ],
                                    ],
                                    'required' => [
                                        'name',
                                        'moons',
                                    ],
                                ],
                                'except' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                        'meta' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'discovered' => [
                                                    'type' => 'integer',
                                                    'const' => 1610,
                                                ],
                                            ],
                                            'required' => [
                                                'discovered',
                                            ],
                                        ],
                                    ],
                                    'required' => [
                                        'name',
                                        'meta',
                                    ],
                                ],
                                'exceptDynamicKey' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                        'moons' => [
                                            'type' => 'integer',
                                            'const' => 2,
                                        ],
                                        'meta' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'discovered' => [
                                                    'type' => 'integer',
                                                    'const' => 1610,
                                                ],
                                                'notes' => [
                                                    'type' => 'string',
                                                    'const' => 'red',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'exceptDynamicIndex' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                        'moons' => [
                                            'type' => 'integer',
                                            'const' => 2,
                                        ],
                                        'meta' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'discovered' => [
                                                    'type' => 'integer',
                                                    'const' => 1610,
                                                ],
                                                'notes' => [
                                                    'type' => 'string',
                                                    'const' => 'red',
                                                ],
                                            ],
                                            'required' => [
                                                'discovered',
                                                'notes',
                                            ],
                                        ],
                                    ],
                                ],
                                'exceptPathInMap' => [
                                    'type' => 'object',
                                    'additionalProperties' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                        'required' => [
                                            'name',
                                        ],
                                    ],
                                ],
                                'onlyListed' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                        'moons' => [
                                            'type' => 'integer',
                                            'const' => 2,
                                        ],
                                    ],
                                ],
                                'exceptListed' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                        'moons' => [
                                            'type' => 'integer',
                                            'const' => 2,
                                        ],
                                        'meta' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'discovered' => [
                                                    'type' => 'integer',
                                                    'const' => 1610,
                                                ],
                                                'notes' => [
                                                    'type' => 'string',
                                                    'const' => 'red',
                                                ],
                                            ],
                                            'required' => [
                                                'discovered',
                                                'notes',
                                            ],
                                        ],
                                    ],
                                    'required' => [
                                        'meta',
                                    ],
                                ],
                                'exceptOneOf' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                        'moons' => [
                                            'type' => 'integer',
                                            'const' => 2,
                                        ],
                                        'meta' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'discovered' => [
                                                    'type' => 'integer',
                                                    'const' => 1610,
                                                ],
                                                'notes' => [
                                                    'type' => 'string',
                                                    'const' => 'red',
                                                ],
                                            ],
                                            'required' => [
                                                'discovered',
                                                'notes',
                                            ],
                                        ],
                                    ],
                                    'required' => [
                                        'meta',
                                    ],
                                ],
                                'onlyOpen' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                        'Venus' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => [
                                                    'type' => 'integer',
                                                ],
                                                'name' => [
                                                    'type' => 'string',
                                                ],
                                            ],
                                            'required' => [
                                                'id',
                                                'name',
                                            ],
                                        ],
                                    ],
                                    'required' => [
                                        'name',
                                    ],
                                ],
                                'exceptOpenList' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => [
                                                    'integer',
                                                    'null',
                                                ],
                                            ],
                                        ],
                                        [
                                            'type' => 'object',
                                            'properties' => [
                                                0 => [
                                                    'type' => 'integer',
                                                ],
                                            ],
                                            'required' => [
                                                '0',
                                            ],
                                            'additionalProperties' => [
                                                'type' => [
                                                    'integer',
                                                    'null',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'exceptPathInOpenMap' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'total' => [
                                            'type' => 'integer',
                                            'const' => 2,
                                        ],
                                    ],
                                    'required' => [
                                        'total',
                                    ],
                                    'additionalProperties' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => [
                                                'type' => 'integer',
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                        'required' => [
                                            'name',
                                        ],
                                    ],
                                ],
                                'onlyOpenKeyList' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                        'moons' => [
                                            'type' => 'integer',
                                            'const' => 2,
                                        ],
                                        'meta' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'discovered' => [
                                                    'type' => 'integer',
                                                    'const' => 1610,
                                                ],
                                                'notes' => [
                                                    'type' => 'string',
                                                    'const' => 'red',
                                                ],
                                            ],
                                            'required' => [
                                                'discovered',
                                                'notes',
                                            ],
                                        ],
                                    ],
                                    'required' => [
                                        'meta',
                                    ],
                                ],
                            ],
                            'required' => [
                                'only',
                                'except',
                                'exceptDynamicKey',
                                'exceptDynamicIndex',
                                'exceptPathInMap',
                                'onlyListed',
                                'exceptListed',
                                'exceptOneOf',
                                'onlyOpen',
                                'exceptOpenList',
                                'exceptPathInOpenMap',
                                'onlyOpenKeyList',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function keySubsets(): mixed
    {
        $planet = $this->planet();

        return [
            'only' => Arr::only($planet, ['name', 'moons', 'rings']),
            'except' => Arr::except($planet, ['moons', 'meta.notes']),
            'exceptDynamicKey' => Arr::except($planet, $this->keyName),
            'exceptDynamicIndex' => Arr::except($planet, $this->index),
            'exceptPathInMap' => Arr::except($this->planetsByName, 'Mars.id'),
            'onlyListed' => Arr::only($planet, $this->fields),
            'exceptListed' => Arr::except($planet, $this->fields),
            'exceptOneOf' => Arr::except($planet, $this->compact ? 'name' : 'moons'),
            'onlyOpen' => Arr::only([...$this->planetsByName, 'name' => 'Mars'], ['name', 'Venus']),
            'exceptOpenList' => Arr::except($this->rankedIds, [1]),
            'exceptPathInOpenMap' => Arr::except([...$this->planetsByName, 'total' => 2], 'Venus.id'),
            'onlyOpenKeyList' => Arr::only($planet, ['first' => 'meta', ...$this->fields]),
        ];
    }


    /**
     * Arr path reads
     */
    #[ExpectedOperationSchema([
        'summary' => 'Arr path reads',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'get' => [
                                    'type' => 'integer',
                                    'const' => 1610,
                                ],
                                'getMissing' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'string',
                                            'const' => 'unknown',
                                        ],
                                        [
                                            'type' => 'number',
                                            'const' => 6.4,
                                            'format' => 'float',
                                        ],
                                    ],
                                ],
                                'pluck' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                        'enum' => [
                                            'Mars',
                                            'Venus',
                                        ],
                                    ],
                                ],
                                'pluckKeyed' => [
                                    'type' => 'object',
                                    'additionalProperties' => [
                                        'type' => 'string',
                                        'enum' => [
                                            'Mars',
                                            'Venus',
                                        ],
                                    ],
                                ],
                                'keyBy' => [
                                    'type' => 'object',
                                    'additionalProperties' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'id' => [
                                                'type' => 'integer',
                                                'enum' => [
                                                    1,
                                                    2,
                                                ],
                                            ],
                                            'name' => [
                                                'type' => 'string',
                                                'enum' => [
                                                    'Mars',
                                                    'Venus',
                                                ],
                                            ],
                                        ],
                                        'required' => [
                                            'id',
                                            'name',
                                        ],
                                    ],
                                ],
                                'dataGet' => [
                                    'type' => 'integer',
                                    'const' => 1610,
                                ],
                                'dataGetOpen' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                ],
                                'dataGetOpenListIndex' => [
                                    'type' => [
                                        'integer',
                                        'null',
                                    ],
                                ],
                            ],
                            'required' => [
                                'get',
                                'getMissing',
                                'pluck',
                                'pluckKeyed',
                                'keyBy',
                                'dataGet',
                                'dataGetOpen',
                                'dataGetOpenListIndex',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function pathReads(): mixed
    {
        $planet = $this->planet();
        $rows = $this->rows();

        return [
            'get' => Arr::get($planet, 'meta.discovered'),
            'getMissing' => Arr::get($this->compact ? $planet : ['meta' => ['mass' => 6.4]], 'meta.mass', fn () => 'unknown'),
            'pluck' => Arr::pluck($rows, 'name'),
            'pluckKeyed' => Arr::pluck($rows, 'name', 'id'),
            'keyBy' => Arr::keyBy($rows, 'id'),
            'dataGet' => data_get($planet, 'meta.discovered'),
            'dataGetOpen' => data_get([...$this->planetsByName, 'total' => 2], 'Venus.name'),
            'dataGetOpenListIndex' => data_get($this->rankedIds, '1'),
        ];
    }


    /**
     * Arr single entries
     */
    #[ExpectedOperationSchema([
        'summary' => 'Arr single entries',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'first' => [
                                    'type' => 'string',
                                    'const' => 'Mars',
                                ],
                                'last' => [
                                    'type' => [
                                        'integer',
                                        'null',
                                    ],
                                ],
                                'firstMatching' => [
                                    'type' => [
                                        'integer',
                                        'null',
                                    ],
                                    'enum' => [
                                        2,
                                        null,
                                    ],
                                ],
                                'wrap' => [
                                    'type' => 'array',
                                    'prefixItems' => [
                                        [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                    ],
                                    'minItems' => 1,
                                    'maxItems' => 1,
                                ],
                                'firstOfOpen' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'integer',
                                            'const' => 2,
                                        ],
                                        [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => [
                                                    'type' => 'integer',
                                                ],
                                                'name' => [
                                                    'type' => 'string',
                                                ],
                                            ],
                                            'required' => [
                                                'id',
                                                'name',
                                            ],
                                        ],
                                    ],
                                ],
                                'lastOfOpenMatching' => [
                                    'type' => [
                                        'object',
                                        'null',
                                    ],
                                    'properties' => [
                                        'id' => [
                                            'type' => 'integer',
                                        ],
                                        'name' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => [
                                        'id',
                                        'name',
                                    ],
                                ],
                                'firstOfOpenList' => [
                                    'type' => [
                                        'integer',
                                        'null',
                                    ],
                                ],
                            ],
                            'required' => [
                                'first',
                                'last',
                                'firstMatching',
                                'wrap',
                                'firstOfOpen',
                                'lastOfOpenMatching',
                                'firstOfOpenList',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function singleEntries(): mixed
    {
        return [
            'first' => Arr::first($this->planet()),
            'last' => Arr::last($this->ids, default: 0),
            'firstMatching' => Arr::first(['name' => 'Mars', 'moons' => 2], fn ($value) => is_int($value)),
            'wrap' => Arr::wrap('Mars'),
            'firstOfOpen' => Arr::first([...$this->planetsByName, 'total' => 2]),
            'lastOfOpenMatching' => Arr::last([...$this->planetsByName, 'total' => 2], fn ($value) => is_array($value)),
            'firstOfOpenList' => Arr::first($this->rankedIds),
        ];
    }


    /**
     * Arr mapping
     */
    #[ExpectedOperationSchema([
        'summary' => 'Arr mapping',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'map' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'mars' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'name' => [
                                                    'type' => 'string',
                                                    'const' => 'mars',
                                                ],
                                                'moons' => [
                                                    'type' => 'integer',
                                                    'const' => 2,
                                                ],
                                            ],
                                            'required' => [
                                                'name',
                                                'moons',
                                            ],
                                        ],
                                        'venus' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'name' => [
                                                    'type' => 'string',
                                                    'const' => 'venus',
                                                ],
                                                'moons' => [
                                                    'type' => 'integer',
                                                    'const' => 0,
                                                ],
                                            ],
                                            'required' => [
                                                'name',
                                                'moons',
                                            ],
                                        ],
                                    ],
                                    'required' => [
                                        'mars',
                                        'venus',
                                    ],
                                ],
                                'mapWithKeys' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'Mars' => [
                                            'type' => 'integer',
                                            'enum' => [
                                                1,
                                                2,
                                            ],
                                        ],
                                        'Venus' => [
                                            'type' => 'integer',
                                            'enum' => [
                                                1,
                                                2,
                                            ],
                                        ],
                                    ],
                                ],
                                'sortRecursive' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'const' => 'Mars',
                                        ],
                                        'moons' => [
                                            'type' => 'integer',
                                            'const' => 2,
                                        ],
                                        'meta' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'discovered' => [
                                                    'type' => 'integer',
                                                    'const' => 1610,
                                                ],
                                                'notes' => [
                                                    'type' => 'string',
                                                    'const' => 'red',
                                                ],
                                            ],
                                            'required' => [
                                                'discovered',
                                                'notes',
                                            ],
                                        ],
                                    ],
                                    'required' => [
                                        'name',
                                        'moons',
                                        'meta',
                                    ],
                                ],
                                'sortRecursiveNestedList' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'tags' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'string',
                                                'enum' => [
                                                    'red',
                                                    'dusty',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'required' => [
                                        'tags',
                                    ],
                                ],
                                'mapList' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                    'minItems' => 1,
                                ],
                                'mapOpen' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'total' => [
                                            'type' => 'string',
                                            'const' => 'total',
                                        ],
                                    ],
                                    'required' => [
                                        'total',
                                    ],
                                    'additionalProperties' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'mapWithKeysOpen' => [
                                    'type' => 'object',
                                    'additionalProperties' => [
                                        'anyOf' => [
                                            [
                                                'type' => 'integer',
                                                'enum' => [
                                                    1,
                                                    2,
                                                ],
                                            ],
                                            [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => [
                                                        'type' => 'integer',
                                                    ],
                                                    'name' => [
                                                        'type' => 'string',
                                                    ],
                                                ],
                                                'required' => [
                                                    'id',
                                                    'name',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'mapWithKeysByIntId' => [
                                    'anyOf' => [
                                        [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                        [
                                            'type' => 'object',
                                            'additionalProperties' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'required' => [
                                'map',
                                'mapWithKeys',
                                'sortRecursive',
                                'sortRecursiveNestedList',
                                'mapList',
                                'mapOpen',
                                'mapWithKeysOpen',
                                'mapWithKeysByIntId',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function mapping(): mixed
    {
        return [
            'map' => Arr::map(['mars' => 2, 'venus' => 0], fn (int $moons, string $name) => ['name' => $name, 'moons' => $moons]),
            'mapWithKeys' => Arr::mapWithKeys($this->rows(), fn (array $row) => [$row['name'] => $row['id']]),
            'sortRecursive' => Arr::sortRecursive($this->planet()),
            'sortRecursiveNestedList' => Arr::sortRecursive(['tags' => ['red', 'dusty']]),
            'mapList' => Arr::map($this->moonCounts, fn (int $moons) => (string) $moons),
            'mapOpen' => Arr::map(['total' => 2, ...$this->planetsByName], fn (mixed $value, string $key) => $key),
            'mapWithKeysOpen' => Arr::mapWithKeys($this->rows(), fn (array $row) => [...$this->planetsByName, 'total' => $row['id']]),
            'mapWithKeysByIntId' => Arr::mapWithKeys($this->rowsWithIntIds(), fn (array $row) => [$row['id'] => $row['name']]),
        ];
    }


    /**
     * @return array{name: 'Mars', moons: 2, meta: array{discovered: 1610, notes: 'red'}}
     */
    private function planet(): array
    {
        return ['name' => 'Mars', 'moons' => 2, 'meta' => ['discovered' => 1610, 'notes' => 'red']];
    }


    /**
     * @return list<array{id: 1|2, name: 'Mars'|'Venus'}>
     */
    private function rows(): array
    {
        return [['id' => 1, 'name' => 'Mars'], ['id' => 2, 'name' => 'Venus']];
    }


    /**
     * @return list<array{id: int, name: string}>
     */
    private function rowsWithIntIds(): array
    {
        return [['id' => 4, 'name' => 'Mars'], ['id' => 7, 'name' => 'Venus']];
    }
}

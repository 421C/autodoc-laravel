<?php declare(strict_types=1);

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use AutoDoc\Laravel\Tests\TestProject\Entities\RocketCategory;
use AutoDoc\Laravel\Tests\TestProject\Http\AbortController;
use AutoDoc\Laravel\Tests\TestProject\Http\AppHelperController;
use AutoDoc\Laravel\Tests\TestProject\Http\EloquentQueryController;
use AutoDoc\Laravel\Tests\TestProject\Http\FormRequestController;
use AutoDoc\Laravel\Tests\TestProject\Http\InvokableController;
use AutoDoc\Laravel\Tests\TestProject\Http\OrderController;
use AutoDoc\Laravel\Tests\TestProject\Http\PaginationController;
use AutoDoc\Laravel\Tests\TestProject\Http\RawQueryController;
use AutoDoc\Laravel\Tests\TestProject\Http\RequestParamsController;
use AutoDoc\Laravel\Tests\TestProject\Http\ResourceController;
use AutoDoc\Laravel\Tests\TestProject\Http\RouteParametersController;
use AutoDoc\Laravel\Tests\TestProject\Http\ValidationController;
use AutoDoc\Laravel\Tests\TestProject\Http\ViewResponseController;
use AutoDoc\Laravel\Tests\TestProject\Models\Rocket;
use Illuminate\Support\Facades\Route;

Route::post('/test/validation/basic-string-rules', [ValidationController::class, 'basicStringRules']);
Route::post('/test/validation/nested-array-rules', [ValidationController::class, 'nestedArrayRules'])->name('test2');
Route::post('/test/validation/rule-objects', [ValidationController::class, 'ruleObjects']);
Route::post('/test/validation/enum-rules', [ValidationController::class, 'enumRules']);
Route::post('/test/validation/wildcard-array', [ValidationController::class, 'wildcardArrayValidation']);
Route::post('/test/validation/password-rule', [ValidationController::class, 'passwordRule']);
Route::post('/test/validation/url-and-ip', [ValidationController::class, 'urlAndIpValidation']);
Route::post('/test/validation/conditional-rules', [ValidationController::class, 'conditionalRules']);
Route::post('/test/validation/numeric', [ValidationController::class, 'numericValidation']);
Route::post('/test/validation/nested-required-wildcard', [ValidationController::class, 'nestedRequiredWithWildcard']);
Route::post('/test/validation/phpdoc-type', [ValidationController::class, 'phpdocTypeInValidation']);
Route::post('/test/validation/enum-nested-filled', [ValidationController::class, 'enumWithNestedAndFilledRules']);
Route::post('/test/validation/nested-data-with-top-level-description', [ValidationController::class, 'nestedDataWithTopLevelDescription']);
Route::post('/test/validation/unresolved-rules', [ValidationController::class, 'unresolvedRulesStillRecordValidationResponse']);

Route::post('/test/form-request/extra-parameters', [FormRequestController::class, 'itemsRequestWithExtraParameters']);
Route::post('/test/form-request/base', [FormRequestController::class, 'itemsRequest']);
Route::post('/test/form-request/extended', [FormRequestController::class, 'extendedItemsRequest']);

Route::post('/test/resource/single', [ResourceController::class, 'singleResource']);
Route::post('/test/resource/collection', [ResourceController::class, 'resourceCollection']);
Route::post('/test/resource/custom-collection', [ResourceController::class, 'customResourceCollection']);
Route::post('/test/resource/arrayable-collection', [ResourceController::class, 'arrayableCollection']);
Route::post('/test/resource/collection-inside-array', [ResourceController::class, 'collectionInsideArray']);

Route::post('/test/route-params/model-binding/{rocket}', [RouteParametersController::class, 'modelBinding']);
Route::post('/test/route-params/enum-binding/{state}', [RouteParametersController::class, 'enumBinding']);
Route::post('/test/route-params/scalar/{rocketId}', [RouteParametersController::class, 'scalarPathParameter']);

Route::post('/test/pagination/basic', [PaginationController::class, 'basicPagination']);
Route::post('/test/pagination/custom-page-name', [PaginationController::class, 'paginationWithCustomPageName']);
Route::post('/test/pagination/dynamic-columns', [PaginationController::class, 'paginationWithDynamicColumns']);
Route::post('/test/pagination/return-type', [PaginationController::class, 'paginatorFromReturnType']);
Route::post('/test/pagination/all-columns', [PaginationController::class, 'paginationWithAllColumns']);
Route::post('/test/pagination/simple', [PaginationController::class, 'simplePagination']);
Route::post('/test/pagination/cursor', [PaginationController::class, 'cursorPagination']);

Route::post('/test/request-params/header', [RequestParamsController::class, 'headerParameter']);
Route::get('/test/request-params/multiple-validate', [RequestParamsController::class, 'multipleValidateCalls']);
Route::post('/test/request-params/query-mutation', [RequestParamsController::class, 'queryParamWithMutation']);
Route::post('/test/request-params/phpdoc-query', [RequestParamsController::class, 'phpdocQueryParam']);
Route::post('/test/request-params/authorization-header', [RequestParamsController::class, 'authorizationHeader']);
Route::post('/test/request-params/typed-helpers', [RequestParamsController::class, 'typedParameterHelpers']);
Route::post('/test/request-params/dot-notation', [RequestParamsController::class, 'dotNotationParameters']);
Route::post('/test/request-params/only', [RequestParamsController::class, 'requestOnlySubset']);
Route::post('/test/request-params/except', [RequestParamsController::class, 'requestExceptSubset']);
Route::post('/test/request-params/all-after-validation', [RequestParamsController::class, 'requestAllAfterValidation']);
Route::post('/test/request-params/all-subset', [RequestParamsController::class, 'requestAllSubset']);
Route::get('/test/request-params/all-after-query-input', [RequestParamsController::class, 'requestAllAfterQueryInput']);

Route::post('/test/eloquent/query-builder-select', [EloquentQueryController::class, 'queryBuilderWithSelect']);
Route::post('/test/eloquent/select-columns', [EloquentQueryController::class, 'selectWithSpecificColumns']);
Route::post('/test/eloquent/add-select', [EloquentQueryController::class, 'selectWithAddSelect']);
Route::post('/test/eloquent/select-alias', [EloquentQueryController::class, 'selectWithAlias']);
Route::post('/test/eloquent/pluck', [EloquentQueryController::class, 'pluckSingleColumn']);
Route::post('/test/eloquent/all-alias', [EloquentQueryController::class, 'allWithColumnAlias']);
Route::post('/test/eloquent/map-arithmetic', [EloquentQueryController::class, 'collectionMapWithArithmetic']);
Route::post('/test/eloquent/map-property', [EloquentQueryController::class, 'collectionMapWithPropertyAccess']);
Route::post('/test/eloquent/chained-methods', [EloquentQueryController::class, 'chainedCollectionMethods']);
Route::post('/test/eloquent/accessors', [EloquentQueryController::class, 'modelWithAccessorsAndMutatedProperties']);
Route::post('/test/eloquent/first-query', [EloquentQueryController::class, 'firstAndFirstOrFailWithQueryBuilder']);
Route::post('/test/eloquent/first-static', [EloquentQueryController::class, 'firstAndFirstOrFailStatic']);
Route::post('/test/eloquent/collection-literal', [EloquentQueryController::class, 'collectionMethodsOnLiteralArray']);
Route::post('/test/eloquent/count-pluck', [EloquentQueryController::class, 'countAndPluck']);
Route::post('/test/eloquent/select-first', [EloquentQueryController::class, 'selectWithFirst']);
Route::post('/test/eloquent/filter-map', [EloquentQueryController::class, 'filterAndMapCollection']);
Route::post('/test/eloquent/pluck-key', [EloquentQueryController::class, 'pluckWithKey']);
Route::post('/test/eloquent/collection-get', [EloquentQueryController::class, 'collectionGetMethod']);
Route::post('/test/eloquent/insert-count', [EloquentQueryController::class, 'insertGetIdAndCount']);
Route::post('/test/eloquent/sort-values', [EloquentQueryController::class, 'sortByAndValues']);
Route::post('/test/eloquent/map-spread', [EloquentQueryController::class, 'mapWithSpreadOperator']);
Route::post('/test/eloquent/relation-access', [EloquentQueryController::class, 'relationshipAccess']);
Route::post('/test/eloquent/eager-select', [EloquentQueryController::class, 'eagerLoadingWithSelect']);
Route::post('/test/eloquent/nested-eager', [EloquentQueryController::class, 'nestedEagerLoading']);
Route::post('/test/eloquent/nested-relation', [EloquentQueryController::class, 'nestedRelationshipLoading']);
Route::post('/test/eloquent/with-array', [EloquentQueryController::class, 'withArraySyntax']);
Route::post('/test/eloquent/complex-nested', [EloquentQueryController::class, 'complexNestedRelations']);
Route::post('/test/eloquent/conditional-return', [EloquentQueryController::class, 'conditionalReturnWithRelation']);
Route::post('/test/eloquent/model-create', [EloquentQueryController::class, 'modelCreate']);
Route::post('/test/eloquent/model-set-attribute', [EloquentQueryController::class, 'modelSetAttribute']);
Route::post('/test/eloquent/model-set-attribute-value-types', [EloquentQueryController::class, 'modelSetAttributeValueTypes']);
Route::post('/test/eloquent/model-set-attribute-columns', [EloquentQueryController::class, 'modelSetAttributeAlongsideColumns']);
Route::post('/test/eloquent/model-set-attribute-serialized', [EloquentQueryController::class, 'modelSetAttributeSerialized']);
Route::post('/test/eloquent/model-set-attribute-json-path', [EloquentQueryController::class, 'modelSetAttributeJsonPath']);
Route::post('/test/eloquent/model-set-attribute-visibility', [EloquentQueryController::class, 'modelSetAttributeVisibility']);
Route::post('/test/eloquent/model-set-attribute-cast', [EloquentQueryController::class, 'modelSetAttributeCastAttributes']);
Route::post('/test/eloquent/model-set-attribute-loop-non-literal', [EloquentQueryController::class, 'modelSetAttributeNonLiteralKeyInLoop']);
Route::post('/test/eloquent/model-set-attribute-loop-conditional', [EloquentQueryController::class, 'modelSetAttributeConditionalInLoop']);
Route::post('/test/eloquent/model-set-attribute-conditional-branch', [EloquentQueryController::class, 'modelSetAttributeConditionalBranch']);
Route::post('/test/eloquent/model-set-attribute-array-element', [EloquentQueryController::class, 'modelSetAttributeOnArrayElementReceiver']);
Route::post('/test/eloquent/model-set-attribute-property-fetch', [EloquentQueryController::class, 'modelSetAttributeOnPropertyFetchReceiver']);
Route::post('/test/eloquent/model-set-attribute-each-closure', [EloquentQueryController::class, 'modelSetAttributeInEachCallback']);
Route::post('/test/eloquent/model-set-attribute-each-arrow', [EloquentQueryController::class, 'modelSetAttributeInEachArrowFunction']);
Route::post('/test/eloquent/model-set-attribute-each-return', [EloquentQueryController::class, 'modelSetAttributeEachReturnValue']);
Route::post('/test/eloquent/model-direct-assignment', [EloquentQueryController::class, 'modelDirectAssignment']);
Route::post('/test/eloquent/model-direct-assignment-serialized', [EloquentQueryController::class, 'modelDirectAssignmentSerialized']);
Route::post('/test/eloquent/model-direct-assignment-visibility', [EloquentQueryController::class, 'modelDirectAssignmentVisibility']);
Route::post('/test/eloquent/model-direct-assignment-cast', [EloquentQueryController::class, 'modelDirectAssignmentCastAttributes']);
Route::post('/test/eloquent/model-direct-assignment-array-element', [EloquentQueryController::class, 'modelDirectAssignmentOnArrayElementReceiver']);
Route::post('/test/eloquent/model-direct-assignment-property-fetch', [EloquentQueryController::class, 'modelDirectAssignmentOnPropertyFetchReceiver']);
Route::post('/test/eloquent/array-conversion-after-direct-assignment', [EloquentQueryController::class, 'modelArrayConversionAfterDirectAssignment']);
Route::post('/test/eloquent/custom-to-array-parent', [EloquentQueryController::class, 'modelCustomToArrayParent']);
Route::post('/test/eloquent/custom-to-array-attributes', [EloquentQueryController::class, 'modelCustomToArrayAttributes']);
Route::post('/test/eloquent/model-get-attribute', [EloquentQueryController::class, 'modelGetAttribute']);
Route::post('/test/eloquent/array-conversion-after-set', [EloquentQueryController::class, 'modelArrayConversionAfterSetAttribute']);
Route::post('/test/eloquent/builder-scalar-finishers', [EloquentQueryController::class, 'builderScalarFinishers']);
Route::post('/test/eloquent/collection-aggregates', [EloquentQueryController::class, 'collectionAggregates']);
Route::post('/test/eloquent/single-column-finishers', [EloquentQueryController::class, 'singleColumnFinishers']);
Route::post('/test/eloquent/collection-scalar-key-methods', [EloquentQueryController::class, 'collectionScalarAndKeyMethods']);
Route::post('/test/eloquent/collection-sole-where', [EloquentQueryController::class, 'collectionSoleAndWhere']);
Route::post('/test/eloquent/each-reassignment-ignored', [EloquentQueryController::class, 'modelEachCallbackReassignmentIgnored']);
Route::post('/test/eloquent/each-early-stop', [EloquentQueryController::class, 'modelEachCallbackEarlyStop']);
Route::post('/test/eloquent/get-attribute-hidden-after-set', [EloquentQueryController::class, 'modelGetAttributeHiddenAfterSet']);
Route::post('/test/eloquent/direct-assignment-null-cast', [EloquentQueryController::class, 'modelDirectAssignmentNullCast']);
Route::post('/test/eloquent/set-attribute-null-cast', [EloquentQueryController::class, 'modelSetAttributeNullCast']);
Route::post('/test/eloquent/set-attribute-accessor', [EloquentQueryController::class, 'modelSetAttributeAccessorAttributes']);
Route::post('/test/eloquent/set-attribute-null-date', [EloquentQueryController::class, 'modelSetAttributeNullDate']);
Route::post('/test/eloquent/set-attribute-null-class-caster', [EloquentQueryController::class, 'modelSetAttributeNullClassCaster']);
Route::post('/test/eloquent/each-early-stop-existing-property', [EloquentQueryController::class, 'modelEachCallbackEarlyStopExistingProperty']);
Route::post('/test/eloquent/each-return-does-not-mutate-source', [EloquentQueryController::class, 'modelEachReturnValueDoesNotMutateSource']);
Route::post('/test/eloquent/each-reassignment-same-class', [EloquentQueryController::class, 'modelEachCallbackSameClassReassignmentIgnored']);
Route::post('/test/eloquent/each-mutation-before-reassignment', [EloquentQueryController::class, 'modelEachCallbackMutationBeforeReassignment']);
Route::post('/test/eloquent/sole-finisher', [EloquentQueryController::class, 'soleQueryFinisher']);

Route::post('/test/raw-query/map-sum', [RawQueryController::class, 'rawQueryWithMapAndSum']);
Route::post('/test/raw-query/collection-sum', [RawQueryController::class, 'collectionSum']);
Route::post('/test/raw-query/scalar-finishers', [RawQueryController::class, 'rawQueryScalarFinishers']);
Route::post('/test/raw-query/transaction', [RawQueryController::class, 'transaction']);

Route::post('/test/abort/model-guard', [AbortController::class, 'abortWhenModelIsMissing']);
Route::post('/test/abort/abort-if', [AbortController::class, 'abortIfWithMessage']);
Route::post('/test/abort/abort-unless', [AbortController::class, 'abortUnlessWithNamedArguments']);

Route::post('/test/app-helper/model', [AppHelperController::class, 'modelResolvedFromApp']);
Route::post('/test/app-helper/service-method', [AppHelperController::class, 'methodCallOnResolvedService']);
Route::post('/test/app-helper/application-method', [AppHelperController::class, 'methodCallOnApplication']);

Route::post('/test/view/response', [ViewResponseController::class, 'viewResponse']);

Route::patch('/test/orders/{order}', [OrderController::class, 'update']);

Route::post('/test/invoke', InvokableController::class);

Route::get('/test/closure1', (
    /**
     * @return object{test: int}
     */
    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'query',
                'name' => 'email',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                    'format' => 'email',
                ],
            ],
        ],
        'responses' => [
            200 => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'test' => [
                                    'type' => 'integer',
                                ],
                            ],
                            'required' => [
                                'test',
                            ],
                        ],
                    ],
                ],
                'description' => '',
            ],
            422 => [
                'description' => '',
            ],
        ],
    ])] function () {
        request()->validate([
            'email' => 'required|email',
        ]);
    }
));

Route::get('/test/closure2/{category}/search/{rocket:launch_date?}', (
    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'path',
                'name' => 'category',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                    'description' => '[RocketCategory](#/schemas/RocketCategory)',
                    'enum' => [
                        'big',
                        'small',
                    ],
                ],
            ],
            [
                'in' => 'path',
                'name' => 'rocket',
                'schema' => [
                    'type' => 'string',
                    'format' => 'date',
                ],
            ],
        ],
    ])] function (RocketCategory $category, ?Rocket $rocket = null) {}
));

Route::get('/test/closure3/{uuid}/{name}/{version}', (
    #[ExpectedOperationSchema([
        'parameters' => [
            [
                'in' => 'path',
                'name' => 'uuid',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
            ],
            [
                'in' => 'path',
                'name' => 'name',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                    'pattern' => '^[a-zA-Z0-9]+$',
                ],
            ],
            [
                'in' => 'path',
                'name' => 'version',
                'required' => true,
                'schema' => [
                    'type' => 'integer',
                ],
            ],
        ],
    ])] function (string $uuid, string $name, int $version) {}
))->whereUuid('uuid')->whereAlphaNumeric('name');

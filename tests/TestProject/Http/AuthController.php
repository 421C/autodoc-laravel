<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\Attributes\ExpectedOperationSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Tests for the authenticated user helpers.
 */
class AuthController
{
    /**
     * User via auth() on an unguarded route
     */
    #[ExpectedOperationSchema([
        'summary' => 'User via auth() on an unguarded route',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
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
                                'email' => [
                                    'type' => 'string',
                                ],
                                'email_verified_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'created_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'updated_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                            ],
                            'required' => [
                                'id',
                                'name',
                                'email',
                                'email_verified_at',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function nullableUser(): mixed
    {
        return auth()->user();
    }


    /**
     * User via Auth facade on an auth-guarded route
     */
    #[ExpectedOperationSchema([
        'security' => [
            [
                'web' => [],
            ],
        ],
        'summary' => 'User via Auth facade on an auth-guarded route',
        'responses' => [
            401 => [
                'description' => '',
            ],
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'integer',
                                ],
                                'name' => [
                                    'type' => 'string',
                                ],
                                'email' => [
                                    'type' => 'string',
                                ],
                                'email_verified_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'created_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'updated_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                            ],
                            'required' => [
                                'id',
                                'name',
                                'email',
                                'email_verified_at',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function guaranteedUser(): mixed
    {
        return Auth::user();
    }


    /**
     * User via the request on an auth-guarded route
     */
    #[ExpectedOperationSchema([
        'security' => [
            [
                'web' => [],
            ],
        ],
        'summary' => 'User via the request on an auth-guarded route',
        'responses' => [
            401 => [
                'description' => '',
            ],
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'integer',
                                ],
                                'name' => [
                                    'type' => 'string',
                                ],
                                'email' => [
                                    'type' => 'string',
                                ],
                                'email_verified_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'created_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'updated_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                            ],
                            'required' => [
                                'id',
                                'name',
                                'email',
                                'email_verified_at',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function requestUser(Request $request): mixed
    {
        return $request->user();
    }


    /**
     * User via an explicit guard argument
     */
    #[ExpectedOperationSchema([
        'summary' => 'User via an explicit guard argument',
        'responses' => [
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => [
                                'object',
                                'null',
                            ],
                            'properties' => [
                                'id' => [
                                    'type' => 'integer',
                                ],
                                'handle' => [
                                    'type' => 'string',
                                ],
                                'is_super' => [
                                    'type' => 'boolean',
                                ],
                                'created_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'updated_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                            ],
                            'required' => [
                                'id',
                                'handle',
                                'is_super',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function explicitGuardUser(): mixed
    {
        return auth('admin')->user();
    }


    /**
     * User with the guard inferred from auth middleware
     */
    #[ExpectedOperationSchema([
        'security' => [
            [
                'admin' => [],
            ],
        ],
        'summary' => 'User with the guard inferred from auth middleware',
        'responses' => [
            401 => [
                'description' => '',
            ],
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'integer',
                                ],
                                'handle' => [
                                    'type' => 'string',
                                ],
                                'is_super' => [
                                    'type' => 'boolean',
                                ],
                                'created_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'updated_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                            ],
                            'required' => [
                                'id',
                                'handle',
                                'is_super',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function middlewareGuardUser(): mixed
    {
        return auth()->user();
    }


    /**
     * User id via auth()->id() on an auth-guarded route
     */
    #[ExpectedOperationSchema([
        'security' => [
            [
                'web' => [],
            ],
        ],
        'summary' => 'User id via auth()->id() on an auth-guarded route',
        'responses' => [
            401 => [
                'description' => '',
            ],
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'integer',
                                ],
                            ],
                            'required' => [
                                'id',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function userId(): mixed
    {
        return ['id' => auth()->id()];
    }


    /**
     * User with one of multiple middleware guards
     */
    #[ExpectedOperationSchema([
        'security' => [
            [
                'admin' => [],
            ],
            [
                'web' => [],
            ],
        ],
        'summary' => 'User with one of multiple middleware guards',
        'responses' => [
            401 => [
                'description' => '',
            ],
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'anyOf' => [
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'id' => [
                                            'type' => 'integer',
                                        ],
                                        'handle' => [
                                            'type' => 'string',
                                        ],
                                        'is_super' => [
                                            'type' => 'boolean',
                                        ],
                                        'created_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                        'updated_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                    ],
                                    'required' => [
                                        'id',
                                        'handle',
                                        'is_super',
                                        'created_at',
                                        'updated_at',
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
                                        'email' => [
                                            'type' => 'string',
                                        ],
                                        'email_verified_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                        'created_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                        'updated_at' => [
                                            'type' => [
                                                'string',
                                                'null',
                                            ],
                                            'format' => 'date-time',
                                        ],
                                    ],
                                    'required' => [
                                        'id',
                                        'name',
                                        'email',
                                        'email_verified_at',
                                        'created_at',
                                        'updated_at',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function multipleMiddlewareGuards(): mixed
    {
        return auth()->user();
    }


    /**
     * Explicit guard id under different auth middleware
     */
    #[ExpectedOperationSchema([
        'security' => [
            [
                'web' => [],
            ],
        ],
        'summary' => 'Explicit guard id under different auth middleware',
        'responses' => [
            401 => [
                'description' => '',
            ],
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => [
                                        'integer',
                                        'null',
                                    ],
                                ],
                            ],
                            'required' => [
                                'id',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function explicitGuardIdUnderDifferentMiddleware(): mixed
    {
        return ['id' => auth('admin')->id()];
    }


    /**
     * Default guard id under explicit basic auth
     */
    #[ExpectedOperationSchema([
        'security' => [
            [
                'basicAuth' => [],
            ],
        ],
        'summary' => 'Default guard id under explicit basic auth',
        'responses' => [
            401 => [
                'description' => '',
            ],
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => [
                                        'integer',
                                        'null',
                                    ],
                                ],
                            ],
                            'required' => [
                                'id',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function defaultGuardIdUnderBasicAuth(): mixed
    {
        return ['id' => auth()->id()];
    }


    /**
     * Explicit guard id under matching basic auth
     */
    #[ExpectedOperationSchema([
        'security' => [
            [
                'basicAuth' => [],
            ],
        ],
        'summary' => 'Explicit guard id under matching basic auth',
        'responses' => [
            401 => [
                'description' => '',
            ],
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'integer',
                                ],
                            ],
                            'required' => [
                                'id',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function explicitGuardIdUnderBasicAuth(): mixed
    {
        return ['id' => auth('admin')->id()];
    }


    /**
     * User via the request on a Sanctum-guarded route
     */
    #[ExpectedOperationSchema([
        'security' => [
            [
                'sanctum' => [],
            ],
        ],
        'summary' => 'User via the request on a Sanctum-guarded route',
        'responses' => [
            401 => [
                'description' => '',
            ],
            200 => [
                'description' => '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'integer',
                                ],
                                'name' => [
                                    'type' => 'string',
                                ],
                                'email' => [
                                    'type' => 'string',
                                ],
                                'email_verified_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'created_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                                'updated_at' => [
                                    'type' => [
                                        'string',
                                        'null',
                                    ],
                                    'format' => 'date-time',
                                ],
                            ],
                            'required' => [
                                'id',
                                'name',
                                'email',
                                'email_verified_at',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])]
    public function sanctumUser(Request $request): mixed
    {
        return $request->user();
    }


    #[ExpectedOperationSchema([
        'responses' => [
            401 => [
                'description' => '',
            ],
            403 => [
                'description' => '',
            ],
        ],
        'security' => [
            [
                'sanctum' => [
                    'reports.view',
                ],
            ],
        ],
    ])]
    public function sanctumAbilities(): void {}


    #[ExpectedOperationSchema([
        'responses' => [
            401 => [
                'description' => '',
            ],
        ],
    ])]
    public function unmappedGuard(): void {}


    #[ExpectedOperationSchema([
        'responses' => [
            401 => [
                'description' => '',
            ],
        ],
        'security' => [
            [
                'sanctum' => [],
            ],
            [
                'basicAuth' => [],
            ],
        ],
    ])]
    public function tokenAndBasicAuth(): void {}


    #[ExpectedOperationSchema([
        'responses' => [
            403 => [
                'description' => '',
            ],
        ],
    ])]
    public function gateAbility(): void {}


    #[ExpectedOperationSchema([
        'responses' => [
            403 => [
                'description' => '',
            ],
        ],
    ])]
    public function policyAbility(): void {}


    #[ExpectedOperationSchema([
        'responses' => [
            429 => [
                'description' => '',
            ],
        ],
    ])]
    public function throttled(): void {}


    #[ExpectedOperationSchema([
        'security' => [
            [
                'web' => [
                    'viewReports',
                ],
            ],
        ],
        'responses' => [
            401 => [
                'description' => '',
            ],
            403 => [
                'description' => '',
            ],
            429 => [
                'description' => '',
            ],
        ],
    ])]
    public function authorizedAndThrottled(): void {}
}

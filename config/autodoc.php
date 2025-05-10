<?php declare(strict_types=1);

return [
    /**
     * Information about your API that will be included in the exported OpenApi schema.
     */
    'api' => [
        'title' => env('APP_NAME', ''),
        'version' => env('APP_VERSION', ''),
        'domain' => env('APP_URL', ''),
        'description' => '',
    ],

    /**
     * List of workspaces and their configuration.
     * Each workspace can contain multiple routes and will be exported as a separate OpenApi schema.
     */
    'workspaces' => [
        'your-api-name' => [
            'routes' => [
                '/',
            ],
        ],
    ],

    /**
     * Configuration options for autodoc Laravel integration.
     */
    'laravel' => [
        /**
         * Documentation page URL.
         */
        'url' => '/api-docs',

        /**
         * Middleware to use for autodoc routes.
         */
        'middleware' => [
            'web',
        ],

        /**
         * Allow some validation rules to automatically generated parameter descriptions.
         */
        'generate_descriptions_from_validation_rules' => true,

        /**
         * Format automatically generated descriptions. Specify a string in `sprintf` format or null.
         */
        'format_generated_descriptions' => '<p style="color: var(--color-text-muted); font-size: 11px;">%s</p>',
    ],

    /**
     * Documentation page UI settings.
     */
    'ui' => [
        /**
         * Documentation page theme - light / dark.
         */
        'theme' => 'light',

        /**
         * Logo URL to show in documentation page.
         */
        'logo' => '',

        /**
         * Hide "Try it" panel in the documentation UI.
         */
        'hide_try_it' => false,
    ],

    'openapi' => [
        /**
         * When enabled, will show routes instead of operation names in sidebar and title.
         */
        'show_routes_as_titles' => true,

        /**
         * When enabled, will attempt to read possible values for returned scalar types.
         * Disabled by default because it is not guaranteed that all possible values will be detected.
         */
        'show_values_for_scalar_types' => false,
    ],

    /**
     * Directory where OpenApi schema files will be exported.
     */
    'openapi_export_dir' => storage_path('openapi'),

    /**
     * Enum handling settings.
     */
    'enum' => [
        /**
         * When enabled, all referenced enums will be exported as OpenApi schema components.
         * Otherwise only enums specified in `schemas` parameter will be exported.
         */
        'autodetect_components' => true,

        /**
         * Remove namespace from enum name.
         */
        'remove_namespace' => true,

        /**
         * Ignore description from enum PHPDoc comment.
         */
        'remove_description' => false,

        /**
         * Create links to enum schemas in the description of enum types.
         */
        'create_links' => true,

        /**
         * Show allowed values for enum types.
         */
        'show_allowed_values' => true,

        /**
         * OpenApi 3.1 does not support descriptions for enum cases but we can generate a HTML
         * description for the whole enum containing all its cases and their descriptions.
         */
        'generate_description_from_cases' => true,
    ],

    /**
     * Class that will be used to load and analyze routes.
     * This class must extend `AutoDoc\AbstractRouteLoader`.
     */
    'route_loader' => AutoDoc\Laravel\RouteLoader::class,

    /**
     * List of extensions that will be loaded.
     */
    'extensions' => [
        AutoDoc\Laravel\Extensions\RequestValidate::class,
        AutoDoc\Laravel\Extensions\ResponseJson::class,
        AutoDoc\Laravel\Extensions\EloquentModelStaticCall::class,
        AutoDoc\Laravel\Extensions\ValidationRuleStaticCall::class,
        AutoDoc\Laravel\Extensions\EloquentModel::class,
        AutoDoc\Laravel\Extensions\CustomFormRequest::class,
        AutoDoc\Laravel\Extensions\ResourceCollectionJson::class,
        AutoDoc\Laravel\Extensions\ResourceJson::class,
        AutoDoc\Laravel\Extensions\ResourceStaticCall::class,
        AutoDoc\Laravel\Extensions\RedirectResponse::class,
    ],

    /**
     * Enable or disable OpenApi schema caching.
     * If this is disabled, schema will be generated on each request.
     */
    'use_cache' => env('APP_ENV') === 'production',

    /**
     * Memory limit for OpenApi schema generation.
     */
    'memory_limit' => null,

    /**
     * Maximum depth of nested types.
     */
    'max_depth' => 7,

    'debug' => [
        /**
         * Enable or disable error reporting.
         */
        'enabled' => false, // filter_var(env('APP_DEBUG'), FILTER_VALIDATE_BOOL),

        /**
         * Ignore errors about non-existant methods on classes that have a
         * __call or __callStatic magic method.
         */
        'ignore_dynamic_method_errors' => true,
    ],
];

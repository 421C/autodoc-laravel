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
         * Documentation page URL. Set to null to disable documentation routes.
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

        /**
         * When enabled, will load all autodoc-laravel built-in extensions.
         */
        'autoload_builtin_extensions' => true,

        /**
         * When disabled, autodoc-laravel will ignore unknown/dynamic methods while parsing Laravel Query Builder.
         */
        'abandon_query_builder_parsing_on_unknown_methods' => false,
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
         * It is not guaranteed that all possible values will be detected, so depending on
         * your project you might want to disable this.
         */
        'show_values_for_scalar_types' => true,

        /**
         * When enabled, will use pattern instead of format for numeric string types.
         */
        'use_pattern_for_numeric_strings' => false,
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

    'classes' => [
        /**
         * Ignore description from class PHPDoc comment.
         */
        'remove_description' => false,
    ],

    'arrays' => [
        'remove_scalar_type_values_when_merging_with_unknown_types' => true,

        /**
         * Force arrays to be resolved as shapes if there is at least one known key.
         * If disabled, arrays will be resolved as shapes only if all keys are known.
         */
        'resolve_partial_shapes' => false,

        /**
         * When enabled, array shapes will be merged in type unions.
         */
        'merge_shapes_in_type_unions' => false,
    ],

    'objects' => [
        /**
         * When enabled, object shapes will be merged in type unions.
         */
        'merge_shapes_in_type_unions' => false,
    ],

    /**
     * Class that will be used to load and analyze routes.
     * This class must extend `AutoDoc\AbstractRouteLoader`.
     */
    'route_loader' => AutoDoc\Laravel\RouteLoader::class,

    /**
     * List of extensions that will be loaded.
     *
     * If `laravel.autoload_builtin_extensions` is enabled, all autodoc-laravel built-in
     * extensions will be loaded automatically so you don't have to specify them here.
     */
    'extensions' => [],

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
    'max_depth' => 15,

    'debug' => [
        /**
         * Enable or disable error reporting.
         */
        'enabled' => false, // filter_var(env('APP_DEBUG'), FILTER_VALIDATE_BOOL),

        /**
         * Ignore errors about unknown methods on classes that have a
         * __call or __callStatic magic method.
         */
        'ignore_dynamic_method_errors' => true,

        /**
         * Ignore errors about unknown methods in traits.
         */
        'ignore_unknown_method_errors_in_traits' => true,
    ],

    /**
     * Read `@autodoc` tags in TypeScript code and export specified structures as TypeScript types.
     */
    'typescript' => [
        'working_directory' => resource_path(),
        'file_extensions' => ['ts', 'tsx', 'vue'],

        'indent' => '    ',
        'string_quote' => "'",
        'add_semicolons' => false,

        /**
         * When enabled, will attempt to read possible values for returned scalar types.
         * It is not guaranteed that all possible values will be detected, so depending on
         * your project you might want to disable this.
         */
        'show_values_for_scalar_types' => true,

        /**
         * Specify a path to a file where all TypeScript types will be saved.
         * Set to `null` to export types right after their `@autodoc` comments.
         */
        'save_types_in_single_file' => null,

        /**
         * Custom modes that can be applied to specific TypeScript exports.
         * For example below, you can specify `{mode: 'export'}` in your `@autodoc` tag to export types to a separate file.
         * Options supported in mode definitions: 'save_types_in_single_file', 'show_values_for_scalar_types', 'indent', 'string_quote', 'add_semicolons'.
         *
         * @example [
         *     'export' => [
         *         'save_types_in_single_file' => '@/path/to/your/exported-types.ts',
         *     ],
         * ]
         */
        'modes' => [],

        /**
         * Path prefixes from your TypeScript project. Used when generating `import(path).Type` statements.
         * Expects either a function of invokable class that returns an iterable of prefix => path pairs.
         *
         * @var (callable(AutoDoc\Config): iterable) | class-string<object&callable(AutoDoc\Config): iterable>
         */
        'path_prefixes' => AutoDoc\TypeScript\TSConfigPathPrefixesLoader::class,

        /**
         * Specify a full path to a tsconfig.json file.
         * Required only if you use the built-in TSConfigPathPrefixesLoader in `path_prefixes` option.
         */
        'tsconfig_path' => __DIR__ . '/../tsconfig.json',

        /**
         * Export request and response schemas as TypeScript types.
         *
         * @example [
         *     '@/exported-types/document-requests-and-responses.ts' => [
         *         'routes' => ['/api/document'],
         *         'request_methods' => ['get', 'post', 'put', 'patch', 'delete'],
         *     ],
         * ]
         */
        'export_http_requests_and_responses' => [],
    ],
];

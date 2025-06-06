# PHP autodoc-laravel

This is a Laravel integration of [PHP autodoc](https://github.com/421C/autodoc-php).

PHP autodoc automatically generates OpenAPI 3.1.0 documentation for your PHP projects by analyzing your codebase. This ensures your API docs are always up-to-date and easy to maintain.

This package provides seamless integration with routes, request validation, database models, API resources, and more.

**Visit [phpautodoc.com](https://phpautodoc.com) to see full documentation.**


## Installation

To install PHP autodoc in a Laravel project, simply install the `421c/autodoc-laravel` package.

```
composer require 421c/autodoc-laravel
```

Then copy configuration file to your project using the command below.

```
php artisan vendor:publish --provider="AutoDoc\Laravel\Providers\AutoDocServiceProvider"
```

Open your `config/autodoc.php` file and set `openapi_export_dir` setting to a directory where you want to save OpenApi 3.1.0 schema JSON files generated by this package.
Make sure this directory exists and is writable.

In your configuration file you can also specify URL to your API docs page with `laravel.url` setting.
If you left it unchanged, you can visit `/api-docs` route to see the generated documentation.

To improve the generated documentation, see [tips to improve the generated documentation](https://phpautodoc.com/#tips-to-improve-the-generated-documentation).

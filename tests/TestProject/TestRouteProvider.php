<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject;

use Illuminate\Support\ServiceProvider;


class TestRouteProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        config()->set('autodoc.workspaces.your-api-name.routes.0', '/test/');
        config()->set('autodoc.openapi.show_routes_as_titles', false);
        config()->set('autodoc.debug.enabled', true);
        config()->set('autodoc.debug.ignore_dynamic_method_errors', false);
        config()->set('autodoc.openapi_export_dir', storage_path());
        config()->set('autodoc.laravel.format_generated_descriptions', null);

        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}

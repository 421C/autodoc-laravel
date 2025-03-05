<?php

namespace AutoDoc\Laravel\Tests\TestProject;

use Illuminate\Support\ServiceProvider;


class TestRouteProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        config()->set('autodoc.workspaces.0.routes.0', '/test/');
        config()->set('autodoc.openapi.show_routes_as_titles', false);

        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}

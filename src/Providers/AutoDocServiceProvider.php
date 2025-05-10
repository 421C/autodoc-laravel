<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Providers;

use AutoDoc\Laravel\Console\Commands\ExportOpenApiSchema;
use Illuminate\Support\ServiceProvider;

class AutoDocServiceProvider extends ServiceProvider
{
    protected function getConfigFile(): string
    {
        return __DIR__ . '/../../config/autodoc.php';
    }

    /**
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportOpenApiSchema::class,
            ]);
        }

        $this->publishes([
            $this->getConfigFile() => config_path('autodoc.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->getConfigFile(), 'autodoc');
    }
}

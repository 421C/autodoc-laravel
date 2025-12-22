<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Providers;

use AutoDoc\Laravel\Console\Commands\ExportOpenApiSchema;
use AutoDoc\Laravel\Console\Commands\ProcessAutoDocDebugTags;
use AutoDoc\Laravel\Console\Commands\UpdateTypeScriptStructures;
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
                UpdateTypeScriptStructures::class,
                ProcessAutoDocDebugTags::class,
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

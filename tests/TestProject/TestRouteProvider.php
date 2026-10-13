<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject;

use AutoDoc\Laravel\Tests\TestProject\Models\AdminUser;
use AutoDoc\Laravel\Tests\TestProject\Models\User;
use Illuminate\Support\ServiceProvider;


class TestRouteProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        config()->set('autodoc.workspaces.your-api-name.routes.0', '/test/');
        config()->set('autodoc.openapi.show_values_for_scalar_types', true);
        config()->set('autodoc.debug.enabled', true);
        config()->set('autodoc.debug.ignore_dynamic_method_errors', false);
        config()->set('autodoc.openapi_export_dir', storage_path());
        config()->set('autodoc.laravel.format_generated_descriptions', null);

        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admins']);
        config()->set('auth.guards.database', ['driver' => 'session', 'provider' => 'database_users']);
        config()->set('auth.guards.sanctum', ['driver' => 'sanctum', 'provider' => null]);
        config()->set('sanctum.guard', ['web']);

        config()->set('autodoc.openapi.security_schemes', [
            'sanctum' => ['type' => 'http', 'scheme' => 'bearer'],
            'admin' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-Admin-Token'],
            'web' => ['type' => 'apiKey', 'in' => 'cookie', 'name' => 'laravel_session'],
            'basicAuth' => ['type' => 'http', 'scheme' => 'basic'],
        ]);
        config()->set('auth.providers.users', ['driver' => 'eloquent', 'model' => User::class]);
        config()->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => AdminUser::class]);
        config()->set('auth.providers.database_users', ['driver' => 'database', 'table' => 'users']);

        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}

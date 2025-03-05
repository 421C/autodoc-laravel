<?php declare(strict_types=1);

namespace AutoDoc\Laravel;

use AutoDoc\AbstractRouteLoader;
use AutoDoc\Route;
use Closure;
use Illuminate\Routing\RouteAction;
use Illuminate\Support\Facades\Route as LaravelRouteFacade;


class RouteLoader extends AbstractRouteLoader
{
    public function getRoutes(): iterable
    {
        $laravelRoutes = LaravelRouteFacade::getRoutes()->getRoutesByMethod();

        foreach ($laravelRoutes as $method => $routes) {
            $method = strtolower($method);

            foreach ($routes as $route) {
                if (is_string($route->action['uses'])) {
                    if (! RouteAction::containsSerializedClosure($route->action)) {
                        if (str_contains($route->action['uses'], '@')) {
                            [$controllerClass, $controllerMethod] = explode('@', $route->action['uses']);

                        } else {
                            $controllerClass = $route->action['uses'];
                            $controllerMethod = '__invoke';
                        }

                        if (class_exists($controllerClass)) {
                            yield new Route(
                                uri: $route->uri,
                                method: $method,
                                className: $controllerClass,
                                classMethod: $controllerMethod,
                            );
                        }
                    }

                } else if ($route->action['uses'] instanceof Closure) {
                    yield new Route(
                        uri: $route->uri,
                        method: $method,
                        closure: $route->action['uses'],
                    );
                }
            }
        }
    }
}

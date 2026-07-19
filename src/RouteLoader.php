<?php declare(strict_types=1);

namespace AutoDoc\Laravel;

use AutoDoc\AbstractRouteLoader;
use AutoDoc\Route;
use Closure;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Routing\RouteAction;
use Illuminate\Support\Facades\Route as LaravelRouteFacade;
use Throwable;


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
                                uri: preg_replace('/\{(\w+)\?\}/', '{$1}', $route->uri),
                                method: $method,
                                className: $controllerClass,
                                classMethod: $controllerMethod,
                                meta: [
                                    'pathParameters' => $this->extractRouteParameters($route),
                                    'middleware' => $this->gatherRouteMiddleware($route),
                                ],
                            );
                        }
                    }

                } else if ($route->action['uses'] instanceof Closure) {
                    yield new Route(
                        uri: preg_replace('/\{(\w+)\?\}/', '{$1}', $route->uri),
                        method: $method,
                        closure: $route->action['uses'],
                        meta: [
                            'pathParameters' => $this->extractRouteParameters($route),
                            'middleware' => $this->gatherRouteMiddleware($route),
                        ],
                    );
                }
            }
        }
    }

    /**
     * The route's middleware with named groups expanded and aliases resolved to
     * class names, so extensions can detect auth-guarded routes. Falls back to
     * the declared middleware when resolution is unavailable.
     *
     * @return list<string>
     */
    protected function gatherRouteMiddleware(LaravelRoute $route): array
    {
        try {
            $middleware = LaravelRouteFacade::gatherRouteMiddleware($route);

        } catch (Throwable) {
            $middleware = $route->gatherMiddleware();
        }

        return array_values(array_unique(array_filter($middleware, 'is_string')));
    }


    /**
     * @return list<array{
     *     name: string,
     *     binding: ?string,
     *     pattern: ?string,
     *     optional: bool,
     * }>
     */
    protected function extractRouteParameters(LaravelRoute $route): array
    {
        $params = [];
        $bindingFields = $route->bindingFields();

        preg_match_all('/\{([^}]+)\}/', $route->uri, $matches);

        foreach ($matches[1] as $param) {
            $name = rtrim($param, '?');

            $params[] = [
                'name' => $name,
                'binding' => $bindingFields[$name] ?? null,
                'pattern' => $route->wheres[$name] ?? null,
                'optional' => str_ends_with($param, '?'),
            ];
        }

        return $params;
    }
}

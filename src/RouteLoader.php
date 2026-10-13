<?php declare(strict_types=1);

namespace AutoDoc\Laravel;

use AutoDoc\AbstractRouteLoader;
use AutoDoc\Laravel\Helpers\RecordsErrorResponses;
use AutoDoc\Laravel\Helpers\RouteMiddleware;
use AutoDoc\Route;
use Closure;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Routing\RouteAction;
use Illuminate\Support\Facades\Route as LaravelRouteFacade;
use Throwable;


class RouteLoader extends AbstractRouteLoader
{
    use RecordsErrorResponses;

    public function getRoutes(): iterable
    {
        $laravelRoutes = LaravelRouteFacade::getRoutes()->getRoutesByMethod();

        foreach ($laravelRoutes as $method => $routes) {
            $method = strtolower($method);

            foreach ($routes as $laravelRoute) {
                $route = $this->createRoute($laravelRoute, $method);

                if ($route) {
                    yield $route;
                }
            }
        }
    }


    protected function createRoute(LaravelRoute $laravelRoute, string $method): ?Route
    {
        $uses = $laravelRoute->action['uses'];
        $controllerClass = null;
        $controllerMethod = null;
        $closure = null;

        if (is_string($uses)) {
            if (RouteAction::containsSerializedClosure($laravelRoute->action)) {
                return null;
            }

            [$controllerClass, $controllerMethod] = str_contains($uses, '@')
                ? explode('@', $uses, 2)
                : [$uses, '__invoke'];

            if (! class_exists($controllerClass)) {
                return null;
            }

        } else if ($uses instanceof Closure) {
            $closure = $uses;

        } else {
            return null;
        }

        $middleware = $this->gatherRouteMiddleware($laravelRoute);

        $route = new Route(
            uri: preg_replace('/\{(\w+)\?\}/', '{$1}', $laravelRoute->uri) ?? $laravelRoute->uri,
            method: $method,
            className: $controllerClass,
            classMethod: $controllerMethod,
            closure: $closure,
            meta: [
                'pathParameters' => $this->extractRouteParameters($laravelRoute),
                'middleware' => $middleware,
            ],
        );

        $routeMiddleware = new RouteMiddleware($middleware);

        $this->addMiddlewareErrorResponses($route, $routeMiddleware);
        $this->addSecurityRequirements($route, $routeMiddleware);

        return $route;
    }


    protected function addSecurityRequirements(Route $route, RouteMiddleware $middleware): void
    {
        $securitySchemes = $this->config->getSecuritySchemes();

        $basicSchemeNames = array_keys(array_filter(
            $securitySchemes,
            fn (array $scheme): bool => ($scheme['type'] ?? null) === 'http'
                && is_string($scheme['scheme'] ?? null)
                && strtolower($scheme['scheme']) === 'basic',
        ));

        $schemeNames = [];

        foreach ($middleware->authentications() as $authentication) {
            foreach ($authentication['basic'] ? $basicSchemeNames : $authentication['guardNames'] as $schemeName) {
                if (isset($securitySchemes[$schemeName])) {
                    $schemeNames[$schemeName] = true;
                }
            }
        }

        $abilities = $middleware->abilities();

        foreach (array_keys($schemeNames) as $schemeName) {
            $route->addSecurityRequirement([$schemeName => $abilities]);
        }
    }


    protected function addMiddlewareErrorResponses(Route $route, RouteMiddleware $middleware): void
    {
        if ($middleware->requiresAuthentication()) {
            $this->addUnauthenticatedResponse($route);
        }

        if ($middleware->requiresAuthorization()) {
            $this->addForbiddenResponse($route);
        }

        if ($middleware->isThrottled()) {
            $this->addTooManyRequestsResponse($route);
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

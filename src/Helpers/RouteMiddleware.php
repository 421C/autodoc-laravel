<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\Route;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Routing\Middleware\ThrottleRequests;

final class RouteMiddleware
{
    /**
     * @param list<string> $middleware
     */
    public function __construct(
        private readonly array $middleware,
    ) {}


    public static function fromRoute(?Route $route): self
    {
        $middleware = $route?->meta['middleware'] ?? [];

        if (! is_array($middleware)) {
            return new self([]);
        }

        return new self(array_values(array_filter($middleware, 'is_string')));
    }


    /**
     * @return array{
     *     implicitGuardNames: list<string>,
     *     guaranteedGuardNames: list<string>,
     *     implicitUserGuaranteed: bool,
     * }
     */
    public function authState(): array
    {
        $defaultGuardName = config('auth.defaults.guard');
        $implicitGuardNames = is_string($defaultGuardName) && $defaultGuardName !== '' ? [$defaultGuardName] : [];
        $guaranteedGuardNames = [];
        $implicitUserGuaranteed = false;

        foreach ($this->middleware as $middleware) {
            if ($this->isBasicAuthMiddleware($middleware)) {
                $parameterSlots = $this->middlewareParameterSlots($middleware);
                $usesImplicitGuard = $parameterSlots === [] || $parameterSlots[0] === '';
                $basicGuardNames = $usesImplicitGuard ? $implicitGuardNames : [$parameterSlots[0]];

                if (count($basicGuardNames) === 1) {
                    $guaranteedGuardNames[] = $basicGuardNames[0];
                }

                if ($usesImplicitGuard || $basicGuardNames === $implicitGuardNames) {
                    $implicitUserGuaranteed = $implicitGuardNames !== [];
                }

            } else if ($this->isAuthenticateMiddleware($middleware)) {
                $parameterSlots = $this->middlewareParameterSlots($middleware);
                $guardNames = [];

                foreach ($parameterSlots as $parameterSlot) {
                    if ($parameterSlot === '') {
                        array_push($guardNames, ...$implicitGuardNames);

                    } else {
                        $guardNames[] = $parameterSlot;
                    }
                }

                if ($guardNames !== []) {
                    $implicitGuardNames = array_values(array_unique($guardNames));
                }

                if ($implicitGuardNames !== []) {
                    $implicitUserGuaranteed = true;

                    if (count($implicitGuardNames) === 1) {
                        $guaranteedGuardNames[] = $implicitGuardNames[0];
                    }
                }
            }
        }

        return [
            'implicitGuardNames' => array_values(array_unique($implicitGuardNames)),
            'guaranteedGuardNames' => array_values(array_unique($guaranteedGuardNames)),
            'implicitUserGuaranteed' => $implicitUserGuaranteed,
        ];
    }


    public function requiresAuthentication(): bool
    {
        return array_any(
            $this->middleware,
            fn (string $middleware): bool => $this->isAuthenticateMiddleware($middleware) || $this->isBasicAuthMiddleware($middleware),
        );
    }


    public function requiresAuthorization(): bool
    {
        return array_any(
            $this->middleware,
            fn (string $middleware): bool => $this->matches($middleware, 'can', Authorize::class),
        );
    }


    public function isThrottled(): bool
    {
        return array_any(
            $this->middleware,
            fn (string $middleware): bool => $this->matches($middleware, 'throttle', ThrottleRequests::class),
        );
    }


    private function isAuthenticateMiddleware(string $middleware): bool
    {
        return $this->matches($middleware, 'auth', Authenticate::class);
    }


    private function isBasicAuthMiddleware(string $middleware): bool
    {
        return $this->matches($middleware, 'auth.basic', AuthenticateWithBasicAuth::class);
    }


    /**
     * @param class-string $className
     */
    private function matches(string $middleware, string $alias, string $className): bool
    {
        $name = explode(':', $middleware, 2)[0];

        return strtolower($name) === $alias
            || is_a($name, $className, true);
    }


    /**
     * @return list<string>
     */
    private function middlewareParameterSlots(string $middleware): array
    {
        $parameters = explode(':', $middleware, 2)[1] ?? null;

        return $parameters === null ? [] : array_map(trim(...), explode(',', $parameters));
    }
}

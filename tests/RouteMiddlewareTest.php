<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests;

use AutoDoc\Laravel\Helpers\RouteMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RouteMiddlewareTest extends TestCase
{
    #[Test]
    public function classifiesResolvedMiddlewareClassNames(): void
    {
        $this->assertTrue(new RouteMiddleware([Authenticate::class . ':admin'])->requiresAuthentication());
        $this->assertTrue(new RouteMiddleware([AuthenticateWithBasicAuth::class])->requiresAuthentication());
        $this->assertTrue(new RouteMiddleware([Authorize::class . ':view,rocket'])->requiresAuthorization());
        $this->assertTrue(new RouteMiddleware([ThrottleRequests::class . ':60,1'])->isThrottled());
        $this->assertTrue(new RouteMiddleware([ThrottleRequestsWithRedis::class . ':api'])->isThrottled());
    }


    #[Test]
    public function classifiesUnresolvedAliases(): void
    {
        $this->assertTrue(new RouteMiddleware(['auth'])->requiresAuthentication());
        $this->assertTrue(new RouteMiddleware(['auth.basic:admin'])->requiresAuthentication());
        $this->assertTrue(new RouteMiddleware(['can:viewReports'])->requiresAuthorization());
        $this->assertTrue(new RouteMiddleware(['throttle:60,1'])->isThrottled());
    }


    #[Test]
    public function ignoresUnrelatedMiddleware(): void
    {
        $middleware = new RouteMiddleware(['web', 'signed', 'role:admin', 'authorize']);

        $this->assertFalse($middleware->requiresAuthentication());
        $this->assertFalse($middleware->requiresAuthorization());
        $this->assertFalse($middleware->isThrottled());
    }
}

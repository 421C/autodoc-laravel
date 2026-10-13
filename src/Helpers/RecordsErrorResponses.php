<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

use AutoDoc\DataTypes\UnknownType;
use AutoDoc\Route;

trait RecordsErrorResponses
{
    protected function addModelNotFoundResponse(Route $route): void
    {
        $route->addResponse(status: 404, contentType: 'application/json', body: new UnknownType);
    }

    protected function addValidationErrorResponse(Route $route): void
    {
        $route->addResponse(status: 422, contentType: 'application/json', body: new UnknownType);
    }

    protected function addUnauthenticatedResponse(Route $route): void
    {
        $route->addResponse(status: 401, contentType: 'application/json', body: new UnknownType);
    }

    protected function addForbiddenResponse(Route $route): void
    {
        $route->addResponse(status: 403, contentType: 'application/json', body: new UnknownType);
    }

    protected function addTooManyRequestsResponse(Route $route): void
    {
        $route->addResponse(status: 429, contentType: 'application/json', body: new UnknownType);
    }
}

<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\TestProject\Models\Planet;

class UnknownBuilderMethodController
{
    public function localScope(): mixed
    {
        return Planet::query()->visited()->get();
    }


    public function modelStaticMethod(): mixed
    {
        return Planet::on('sqlite')->get();
    }


    public function unknownMethod(): mixed
    {
        /** @phpstan-ignore method.notFound */
        return Planet::query()->thisIsNotABuilderMethod()->get();
    }
}

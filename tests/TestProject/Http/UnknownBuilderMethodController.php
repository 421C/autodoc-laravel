<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use AutoDoc\Laravel\Tests\TestProject\Models\Planet;
use Illuminate\Support\Facades\DB;

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


    public function databaseConnection(): mixed
    {
        return DB::connection('testing')->table('planets')->get();
    }


    public function unknownMethod(): mixed
    {
        /** @phpstan-ignore method.notFound */
        return Planet::query()->thisIsNotABuilderMethod()->get();
    }
}

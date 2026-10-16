<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Entities;

use Illuminate\Http\Resources\Attributes\Collects;
use Illuminate\Http\Resources\Json\ResourceCollection;

#[Collects(UserResource::class)]
class UserList extends ResourceCollection
{
}

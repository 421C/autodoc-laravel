<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Entities;

enum OrderStatus: int
{
    case Pending = 1;
    case Processing = 2;
    case Completed = 3;
    case Cancelled = 4;
}

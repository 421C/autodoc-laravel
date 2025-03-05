<?php
 
namespace AutoDoc\Laravel\Tests\TestProject\Entities;
 
use Carbon\Carbon;

class User
{
    public int $id;
    public string $name;
    public string $email;
    public Carbon $created_at;
    public ?Carbon $updated_at;
}

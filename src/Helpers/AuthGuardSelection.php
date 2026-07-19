<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Helpers;

final readonly class AuthGuardSelection
{
    /**
     * @param ?list<string> $guardNames
     */
    private function __construct(public ?array $guardNames) {}


    /**
     * The call did not name a guard. Use Laravel’s effective current guard.
     */
    public static function implicit(): self
    {
        return new self([]);
    }


    /**
     * @param list<string> $guardNames
     */
    public static function explicit(array $guardNames): self
    {
        return new self(array_values(array_unique($guardNames)));
    }


    public static function unknown(): self
    {
        return new self(null);
    }


    public function isKnown(): bool
    {
        return $this->guardNames !== null;
    }


    public function isImplicit(): bool
    {
        return $this->guardNames === [];
    }
}

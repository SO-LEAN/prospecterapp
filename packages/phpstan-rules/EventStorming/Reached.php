<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * A method that a chain of calls reaches, for sure or only under a condition met on the way, and the arm of an
 * exclusive choice when that arm is its only condition.
 */
final readonly class Reached
{
    public function __construct(public string $class, public string $method, public bool $certain, public ?Arm $arm = null)
    {
    }
}

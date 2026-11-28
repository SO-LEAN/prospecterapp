<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * A method that calls another one, or creates an object (its target method is then __construct). A call is
 * conditional when it happens in a branch, a loop or after an early return. It tells its arm when its one condition
 * is an arm of an exclusive choice.
 */
final readonly class Call
{
    public const string CREATION = '__construct';

    public function __construct(
        public string $caller,
        public string $callerMethod,
        public string $target,
        public string $targetMethod,
        public bool $conditional = false,
        public ?Arm $arm = null,
    ) {
    }

    public function isCreation(): bool
    {
        return self::CREATION === $this->targetMethod;
    }
}

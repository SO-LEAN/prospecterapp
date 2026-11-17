<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\LocateAgain;

/**
 * Geocodes again the address of an organization located before its municipality was kept (ADR 0142); nothing for
 * one that has it.
 */
final readonly class LocateAgain
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

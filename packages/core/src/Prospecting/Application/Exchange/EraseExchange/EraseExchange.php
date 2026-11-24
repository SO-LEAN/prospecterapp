<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\EraseExchange;

/**
 * Erases an exchange from every timeline.
 */
final readonly class EraseExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

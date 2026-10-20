<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CorrectExchange;

/**
 * Corrects what happened in an exchange, all its facts at once; its contact stays. The channel and the
 * direction are named by the values of `Channel` and `Direction`.
 */
final readonly class CorrectExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $channel,
        public string $direction,
        public \DateTimeImmutable $occurredAt,
        public ?string $text,
        public bool $missed,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

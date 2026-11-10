<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047): the state of an exchange once it changed,
 * as its facts count it. The highest revision is the latest; an erased exchange is absent, at the revision 0,
 * without its moment.
 */
final readonly class ExchangeFactsChanged
{
    public function __construct(
        public string $accountId,
        public string $exchangeId,
        public int $revision,
        public bool $present,
        public ?\DateTimeImmutable $notedAt,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}

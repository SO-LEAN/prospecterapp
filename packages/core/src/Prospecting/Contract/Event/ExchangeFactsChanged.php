<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047): the state of an exchange once it changed,
 * as its facts count it: where it stands (`planned`, `done`, `missed`, `cancelled`) and its moment. The highest
 * revision is the latest; an erased exchange is absent, at the revision 0, without its status nor its moment.
 * Published before it had a status (`ExchangeFactsChanged.v1`), a present exchange has none.
 */
final readonly class ExchangeFactsChanged
{
    public function __construct(
        public string $accountId,
        public string $exchangeId,
        public int $revision,
        public bool $present,
        public ?string $status,
        public ?\DateTimeImmutable $notedAt,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}

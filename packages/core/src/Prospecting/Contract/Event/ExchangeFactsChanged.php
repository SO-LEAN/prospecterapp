<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047): the state of an exchange once it changed,
 * as its facts count it: where it stands (`planned`, `done`, `missed`, `cancelled`) and its moment. The highest
 * revision is the latest; an erased exchange is absent, at the revision 0, without its status nor its moment.
 * Published before it had a status (`ExchangeFactsChanged.v1`), a present exchange has none; before it named its
 * contact and its campaign (`ExchangeFactsChanged.v2`), it names none (ADR 0145). Absent, it says whether the exchange
 * went with its organization, the prospecting of the last one stopped, rather than erased by a member; published
 * before (`ExchangeFactsChanged.v3`), it did not (ADR 0155).
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
        public ?string $contactId = null,
        public ?string $campaignId = null,
        public bool $erasedWithItsOrganization = false,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Event;

/**
 * An integration event published to the other contexts (ADR 0046, 0047). It gives an exchange's state after a change,
 * as its facts count it: its status (`planned`, `done`, `missed`, `cancelled`) and its moment. The highest revision
 * is the latest. An erased exchange is absent, at revision 0, without a status or a moment.
 * An event published before the exchanges had a status (`ExchangeFactsChanged.v1`) gives a present exchange no
 * status. An event published before it named the contact and the campaign (`ExchangeFactsChanged.v2`) names neither
 * (ADR 0145). For an absent exchange, the event says whether the exchange went with its organization, because the
 * prospecting of its last organization stopped, rather than because a member erased it. An event published before
 * (`ExchangeFactsChanged.v3`) does not say it (ADR 0155).
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

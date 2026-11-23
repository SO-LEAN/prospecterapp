<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts;

/**
 * An exchange's facts, the part another context counts. They say whether the account keeps the exchange, its status
 * (`planned`, `done`, `missed`, `cancelled`) and the moment it happens. They name the contact it was with and the
 * campaign it was logged in, if any (ADR 0145). They give its state's revision, which each change increments. For an
 * erased exchange, they say whether it went with its organization, in which case no member erased it (ADR 0155).
 */
final readonly class ExchangeFactsResponse
{
    private const int NO_REVISION = 0;

    public function __construct(
        public string $exchangeId,
        public int $revision,
        public bool $present,
        public ?string $status,
        public ?\DateTimeImmutable $notedAt,
        public ?string $contactId = null,
        public ?string $campaignId = null,
        public bool $erasedWithItsOrganization = false,
    ) {
    }

    /**
     * An erased exchange's last facts. They still name the contact it was with and the campaign it was logged in, when
     * given (ADR 0152). They say whether it went with its organization (ADR 0155).
     */
    public static function absent(string $exchangeId, ?string $contactId = null, ?string $campaignId = null, bool $withItsOrganization = false): self
    {
        return new self($exchangeId, self::NO_REVISION, false, null, null, $contactId, $campaignId, $withItsOrganization);
    }
}

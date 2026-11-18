<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts;

/**
 * The facts of an exchange, what another context counts of it: whether the account keeps it, where it stands
 * (`planned`, `done`, `missed`, `cancelled`), the moment it happens, the contact it was with and the campaign it was
 * noted in, if any (ADR 0145), and the revision of its state, incremented at each change; erased, whether it went
 * with its organization, no member erasing it (ADR 0155).
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
     * An erased exchange: the last of its facts, naming still the contact it was with and the campaign it was noted
     * in when told (ADR 0152), and whether it went with its organization (ADR 0155).
     */
    public static function absent(string $exchangeId, ?string $contactId = null, ?string $campaignId = null, bool $withItsOrganization = false): self
    {
        return new self($exchangeId, self::NO_REVISION, false, null, null, $contactId, $campaignId, $withItsOrganization);
    }
}

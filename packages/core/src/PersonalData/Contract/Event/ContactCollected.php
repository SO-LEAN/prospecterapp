<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047). This member collected a person the account
 * prospects, as a contact of these organizations.
 */
final readonly class ContactCollected
{
    /**
     * @param list<string> $organizationIds
     */
    public function __construct(
        public string $accountId,
        public string $memberId,
        public string $personId,
        public array $organizationIds,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}

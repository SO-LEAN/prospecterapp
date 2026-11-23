<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Event;

/**
 * An integration event published to the other contexts (ADR 0046, 0047). This member's gesture on the organization
 * left these contacts in none of the account's organizations. The contacts must not stay (ADR 0042).
 */
final readonly class ContactsLeftWithoutOrganization
{
    /**
     * @param list<string> $contactIds
     */
    public function __construct(
        public string $accountId,
        public string $memberId,
        public string $organizationId,
        public array $contactIds,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}

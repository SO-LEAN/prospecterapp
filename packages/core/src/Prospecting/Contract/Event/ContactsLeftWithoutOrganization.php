<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0047): the gesture of this member on the
 * organization left these contacts in no organization of the account; they must not stay (ADR 0042).
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

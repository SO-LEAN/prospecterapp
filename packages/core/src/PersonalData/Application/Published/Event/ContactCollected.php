<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Published\Event;

use Prospecter\Core\PersonalData\Domain\Event as Domain;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Integration event published to the other contexts (ADR 0046): a person the account prospects was collected,
 * as a contact of these organizations, by this member.
 */
final readonly class ContactCollected
{
    /**
     * @param list<string> $organizationIds
     */
    private function __construct(
        public string $accountId,
        public string $memberId,
        public string $personId,
        public array $organizationIds,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public static function of(Domain\ContactCollected $event): self
    {
        return new self(
            (string) $event->accountId,
            (string) $event->by,
            (string) $event->person,
            array_map(static fn (OrganizationId $organization): string => (string) $organization, $event->organizations),
            $event->occurredAt,
        );
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Published\Event;

use Prospecter\Core\Prospecting\Domain\Event as Domain;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Integration event published to the other contexts (ADR 0046): the gesture of this member on the
 * organization left these contacts in no organization of the account; they must not stay (ADR 0042).
 */
final readonly class ContactsLeftWithoutOrganization
{
    /**
     * @param list<string> $contactIds
     */
    private function __construct(
        public string $accountId,
        public string $memberId,
        public string $organizationId,
        public array $contactIds,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public static function of(Domain\ContactsLeftWithoutOrganization $event): self
    {
        return new self(
            (string) $event->accountId,
            (string) $event->by,
            (string) $event->organization,
            array_map(static fn (PersonId $contact): string => (string) $contact, $event->contacts),
            $event->occurredAt,
        );
    }
}

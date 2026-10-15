<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Published\Event;

use Prospecter\Core\PersonalData\Domain\Event as Domain;

/**
 * Integration event published to the other contexts (ADR 0046): a contact was erased by this member; no
 * organization may name it any more.
 */
final readonly class ContactForgotten
{
    private function __construct(
        public string $accountId,
        public string $memberId,
        public string $personId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public static function of(Domain\ContactForgotten $event): self
    {
        return new self((string) $event->accountId, (string) $event->by, (string) $event->person, $event->occurredAt);
    }
}

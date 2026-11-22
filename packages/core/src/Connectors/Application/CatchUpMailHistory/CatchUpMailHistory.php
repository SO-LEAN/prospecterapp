<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\CatchUpMailHistory;

/**
 * I catch up the e-mails my mailbox already exchanged with a contact, or with an organization itself: they are
 * imported as the import of my e-mails imports them, asynchronously.
 */
final readonly class CatchUpMailHistory
{
    private function __construct(
        public string $memberId,
        public ?string $contactId,
        public ?string $organizationId,
        public \DateTimeImmutable $executedAt,
    ) {
    }

    public static function ofContact(string $memberId, string $contactId, \DateTimeImmutable $executedAt): self
    {
        return new self(memberId: $memberId, contactId: $contactId, organizationId: null, executedAt: $executedAt);
    }

    public static function ofOrganization(string $memberId, string $organizationId, \DateTimeImmutable $executedAt): self
    {
        return new self(memberId: $memberId, contactId: null, organizationId: $organizationId, executedAt: $executedAt);
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMailCatchUpIntents;

/**
 * Whether I may catch up the e-mails my mailbox exchanged with a contact, or with an organization itself, without
 * reading anything of it: the card offers it only then.
 */
final readonly class ConsultMailCatchUpIntents
{
    private function __construct(
        public string $readerId,
        public ?string $contactId,
        public ?string $organizationId,
    ) {
    }

    public static function ofContact(string $readerId, string $contactId): self
    {
        return new self($readerId, $contactId, null);
    }

    public static function ofOrganization(string $readerId, string $organizationId): self
    {
        return new self($readerId, null, $organizationId);
    }
}

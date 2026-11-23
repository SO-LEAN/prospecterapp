<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMailCatchUpIntents;

/**
 * Whether I may reimport the e-mails that my mailbox exchanged with a contact, or with an organization itself. This
 * query reads no e-mail. The card offers the reimport only when it is possible.
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

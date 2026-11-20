<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0154): an e-mail of the mailbox of this member was
 * exchanged with a contact, or with an organization itself, of their account. Its subject and its text travel with
 * it, as the text of an exchange; no address does.
 */
final readonly class EmailExchanged
{
    public function __construct(
        public string $accountId,
        public string $memberId,
        public string $provider,
        public string $emailId,
        public string $direction,
        public ?string $contactId,
        public ?string $organizationId,
        public string $subject,
        public string $text,
        public \DateTimeImmutable $exchangedAt,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}

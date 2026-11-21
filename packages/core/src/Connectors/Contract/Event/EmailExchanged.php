<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0154): an e-mail of the mailbox of this member was
 * exchanged with a contact, or with an organization itself, of their account. Its subject and its text travel with
 * it, as the text of an exchange; no address does. The id the provider gives the message in this mailbox opens it
 * there (ADR 0174). Its files travel apart: sent to the private storage as uploads, one for this correspondent, which
 * the event names with the name of each file, and those left out of the import, by their name and size (ADR 0182).
 */
final readonly class EmailExchanged
{
    /**
     * @param list<array{upload: string, name: string, size: int}> $attachments        the uploads of its files, for this correspondent
     * @param list<array{name: string, size: int}>                 $attachmentsLeftOut the files not imported
     */
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
        public ?string $providerMessageId = null,
        public array $attachments = [],
        public array $attachmentsLeftOut = [],
    ) {
    }
}

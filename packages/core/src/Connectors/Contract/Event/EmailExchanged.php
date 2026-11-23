<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0154). The member's mailbox exchanged an e-mail with
 * a contact of their account, or with one of the account's organizations at its own address. The event carries the
 * e-mail's subject and text as the exchange's text. It carries no address. The provider's id for the message opens
 * it in this mailbox (ADR 0174). The attachments travel apart (ADR 0182). Each imported attachment is sent to the
 * private storage as an upload for this correspondent, and the event names that upload with the file's name. The
 * event also names the attachments left out of the import, by their name and size.
 */
final readonly class EmailExchanged
{
    /**
     * @param list<array{upload: string, name: string, size: int}> $attachments        the attachments' uploads for this correspondent
     * @param list<array{name: string, size: int}>                 $attachmentsLeftOut the attachments left out of the import
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

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\NoteImportedEmail;

final readonly class NoteImportedEmail
{
    /**
     * @param string                                               $memberId           the member whose mailbox exchanged the e-mail
     * @param list<array{upload: string, name: string, size: int}> $attachments        the attachments' uploads for this correspondent
     * @param list<array{name: string, size: int}>                 $attachmentsLeftOut the attachments left out of the import
     */
    public function __construct(
        public string $memberId,
        public string $provider,
        public string $emailId,
        public string $direction,
        public ?string $contactId,
        public ?string $organizationId,
        public string $subject,
        public string $text,
        public \DateTimeImmutable $exchangedAt,
        public ?string $providerMessageId,
        public array $attachments,
        public array $attachmentsLeftOut,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}

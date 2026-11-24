<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Connectors\Contract\Event\EmailExchanged;
use Prospecter\Core\Prospecting\Application\Exchange\NoteImportedEmail\NoteImportedEmail;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a member's mailbox exchanged an e-mail with a contact or an organization, the e-mail is noted as one of that
 * member's exchanges (ADR 0154).
 */
final readonly class NoteImportedEmailPolicy implements Policy
{
    public function __invoke(EmailExchanged $event): NoteImportedEmail
    {
        return new NoteImportedEmail(
            memberId: $event->memberId,
            provider: $event->provider,
            emailId: $event->emailId,
            direction: $event->direction,
            contactId: $event->contactId,
            organizationId: $event->organizationId,
            subject: $event->subject,
            text: $event->text,
            exchangedAt: $event->exchangedAt,
            providerMessageId: $event->providerMessageId,
            attachments: $event->attachments,
            attachmentsLeftOut: $event->attachmentsLeftOut,
            executedAt: $event->occurredAt,
        );
    }
}

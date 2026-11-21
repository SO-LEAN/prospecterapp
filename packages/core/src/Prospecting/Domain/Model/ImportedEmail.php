<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * Where an exchange imported from a mailbox comes from: the provider of the mailbox and the identity of the e-mail
 * across the mailboxes of the account (ADR 0154), and the id the provider gives the message in the mailbox that
 * imported it, which opens it there, none for one imported before it was kept (ADR 0174).
 */
final readonly class ImportedEmail
{
    public function __construct(
        public string $provider,
        public string $identity,
        public ?string $providerMessageId = null,
    ) {
    }
}

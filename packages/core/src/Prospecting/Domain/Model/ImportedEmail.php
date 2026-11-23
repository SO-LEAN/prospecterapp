<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * Where an exchange imported from a mailbox comes from: the mailbox's provider and the e-mail's identity across the
 * account's mailboxes (ADR 0154). It also holds the id the provider gives the message in the importing mailbox,
 * which opens the message there. An e-mail imported before Prospecter kept that id has none (ADR 0174).
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

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * Where an exchange imported from a mailbox comes from: the provider of the mailbox and the identity of the e-mail
 * across the mailboxes of the account (ADR 0154).
 */
final readonly class ImportedEmail
{
    public function __construct(
        public string $provider,
        public string $identity,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptContactsByEmail implements WhichContactsHaveTheseEmailsOutput
{
    /**
     * @var array<string, string>
     */
    public private(set) array $contacts = [];

    /**
     * @param array<string, string> $contacts
     */
    public function present(array $contacts): void
    {
        $this->contacts = $contacts;
    }
}

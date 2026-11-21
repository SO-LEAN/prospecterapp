<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptEmailsOfContacts implements WhichEmailsHaveTheseContactsOutput
{
    /**
     * @var array<string, ?string>
     */
    public private(set) array $emails = [];

    /**
     * @param array<string, ?string> $emails
     */
    public function present(array $emails): void
    {
        $this->emails = $emails;
    }
}

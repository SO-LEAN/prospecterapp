<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptEmailsOfOrganizations implements WhichEmailsHaveTheseOrganizationsOutput
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

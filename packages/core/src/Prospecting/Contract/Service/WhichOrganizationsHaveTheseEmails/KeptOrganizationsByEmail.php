<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptOrganizationsByEmail implements WhichOrganizationsHaveTheseEmailsOutput
{
    /**
     * @var array<string, string>
     */
    public private(set) array $organizations = [];

    /**
     * @param array<string, string> $organizations
     */
    public function present(array $organizations): void
    {
        $this->organizations = $organizations;
    }
}

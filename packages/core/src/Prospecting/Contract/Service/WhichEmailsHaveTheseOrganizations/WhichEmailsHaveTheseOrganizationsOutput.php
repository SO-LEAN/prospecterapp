<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations;

interface WhichEmailsHaveTheseOrganizationsOutput
{
    /**
     * @param array<string, ?string> $emails the address of each organization, none when it has none, by its identity;
     *                                       an organization the account does not prospect is absent
     */
    public function present(array $emails): void;
}

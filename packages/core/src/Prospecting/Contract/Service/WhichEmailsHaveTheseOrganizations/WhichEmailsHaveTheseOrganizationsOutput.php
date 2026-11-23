<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations;

interface WhichEmailsHaveTheseOrganizationsOutput
{
    /**
     * @param array<string, ?string> $emails each organization's address by its id, null when it has none. An
     *                                       organization the account does not prospect is absent
     */
    public function present(array $emails): void;
}

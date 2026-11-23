<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails;

interface WhichOrganizationsHaveTheseEmailsOutput
{
    /**
     * @param array<string, string> $organizations the id of the organization that has each address, by the address
     *                                             in lower case. An address that none of the account's organizations
     *                                             has is absent
     */
    public function present(array $organizations): void;
}

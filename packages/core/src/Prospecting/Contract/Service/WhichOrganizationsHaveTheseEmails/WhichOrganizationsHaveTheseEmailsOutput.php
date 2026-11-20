<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails;

interface WhichOrganizationsHaveTheseEmailsOutput
{
    /**
     * @param array<string, string> $organizations the identity of the organization having each address, by the
     *                                             address in lower case; an address no organization of the account
     *                                             has is absent
     */
    public function present(array $organizations): void;
}

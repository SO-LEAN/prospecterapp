<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails;

interface WhichContactsHaveTheseEmailsOutput
{
    /**
     * @param array<string, string> $contacts the identity of the contact having each address, by the address in lower
     *                                        case; an address no contact of the account has is absent
     */
    public function present(array $contacts): void;
}

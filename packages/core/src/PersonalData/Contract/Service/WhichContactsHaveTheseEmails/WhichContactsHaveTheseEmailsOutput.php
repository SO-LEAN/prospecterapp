<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails;

interface WhichContactsHaveTheseEmailsOutput
{
    /**
     * @param array<string, string> $contacts the id of the contact who has each address, keyed by the address in
     *                                        lower case; an address that none of the account's contacts has is absent
     */
    public function present(array $contacts): void;
}

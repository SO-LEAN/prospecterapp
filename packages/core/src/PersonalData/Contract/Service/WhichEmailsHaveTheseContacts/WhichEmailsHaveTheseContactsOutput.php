<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts;

interface WhichEmailsHaveTheseContactsOutput
{
    /**
     * @param array<string, ?string> $emails the address of each contact, none when they have none, by their identity;
     *                                       a contact the member does not read in clear is absent
     */
    public function present(array $emails): void;
}

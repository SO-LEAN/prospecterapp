<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts;

interface WhichEmailsHaveTheseContactsOutput
{
    /**
     * @param array<string, ?string> $emails each contact's address, keyed by the contact's id, or none when the
     *                                       contact has none; a contact the member does not read unmasked is absent
     */
    public function present(array $emails): void;
}

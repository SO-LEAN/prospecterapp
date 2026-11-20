<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts;

interface WhichOrganizationsNameTheseContactsOutput
{
    /**
     * @param list<OrganizationsNamingContactResponse> $contacts those an organization the account prospects names, in
     *                                                           the order asked; a contact no organization names is
     *                                                           absent
     */
    public function present(array $contacts): void;
}

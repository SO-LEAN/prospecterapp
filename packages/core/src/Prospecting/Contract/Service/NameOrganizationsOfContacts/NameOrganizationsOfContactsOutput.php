<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts;

interface NameOrganizationsOfContactsOutput
{
    /**
     * @param list<OrganizationsOfContactResponse> $contacts the contacts that an organization the account prospects
     *                                                       names, in the order asked. A contact that no organization
     *                                                       names is absent
     */
    public function present(array $contacts): void;
}

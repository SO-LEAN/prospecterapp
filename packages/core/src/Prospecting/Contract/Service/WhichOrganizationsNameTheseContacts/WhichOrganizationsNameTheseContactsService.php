<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts;

/**
 * A service Prospecting offers the other contexts (ADR 0045, 0089). It refuses nothing. Its answer leaves out what
 * is unknown.
 */
interface WhichOrganizationsNameTheseContactsService
{
    public function __invoke(WhichOrganizationsNameTheseContacts $query, WhichOrganizationsNameTheseContactsOutput $output): void;
}

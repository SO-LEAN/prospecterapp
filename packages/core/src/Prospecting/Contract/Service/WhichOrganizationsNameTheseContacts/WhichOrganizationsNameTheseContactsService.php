<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsNameTheseContacts;

/**
 * The service Prospecting offers the other contexts (ADR 0045, 0089): it refuses nothing, what is unknown is
 * absent.
 */
interface WhichOrganizationsNameTheseContactsService
{
    public function __invoke(WhichOrganizationsNameTheseContacts $query, WhichOrganizationsNameTheseContactsOutput $output): void;
}

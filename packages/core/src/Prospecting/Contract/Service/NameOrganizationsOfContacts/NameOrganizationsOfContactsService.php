<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts;

/**
 * The service Prospecting offers the other contexts (ADR 0045, 0089): it refuses nothing, what is unknown is
 * absent.
 */
interface NameOrganizationsOfContactsService
{
    public function __invoke(NameOrganizationsOfContacts $query, NameOrganizationsOfContactsOutput $output): void;
}

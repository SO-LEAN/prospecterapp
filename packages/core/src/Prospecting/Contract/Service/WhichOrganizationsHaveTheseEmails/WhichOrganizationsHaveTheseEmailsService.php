<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails;

/**
 * A service Prospecting offers the other contexts (ADR 0045, 0089). It refuses nothing. Its answer leaves out what
 * is unknown.
 */
interface WhichOrganizationsHaveTheseEmailsService
{
    public function __invoke(WhichOrganizationsHaveTheseEmails $query, WhichOrganizationsHaveTheseEmailsOutput $output): void;
}

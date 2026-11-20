<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails;

/**
 * The service Prospecting offers the other contexts (ADR 0045, 0089): it refuses nothing, what is unknown is absent.
 */
interface WhichOrganizationsHaveTheseEmailsService
{
    public function __invoke(WhichOrganizationsHaveTheseEmails $query, WhichOrganizationsHaveTheseEmailsOutput $output): void;
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichEmailsHaveTheseOrganizations;

/**
 * The service Prospecting offers the other contexts (ADR 0045, 0089): it refuses nothing, what is unknown is absent.
 */
interface WhichEmailsHaveTheseOrganizationsService
{
    public function __invoke(WhichEmailsHaveTheseOrganizations $query, WhichEmailsHaveTheseOrganizationsOutput $output): void;
}

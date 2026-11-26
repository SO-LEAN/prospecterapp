<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations;

/**
 * A service Prospecting offers the other contexts (ADR 0045, 0089, 0173, 0217): which of the account's organizations
 * some clues designate. It answers one identification per set of clues, in the order asked, and writes nothing.
 */
interface IdentifyOrganizationsService
{
    public function __invoke(IdentifyOrganizations $query, IdentifyOrganizationsOutput $output): void;
}

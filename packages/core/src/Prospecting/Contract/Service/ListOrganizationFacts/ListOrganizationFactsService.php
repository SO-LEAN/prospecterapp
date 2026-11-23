<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts;

/**
 * A service Prospecting offers the other contexts (ADR 0045, 0089). It refuses nothing. An unknown account has no
 * facts.
 */
interface ListOrganizationFactsService
{
    public function __invoke(ListOrganizationFacts $query, ListOrganizationFactsOutput $output): void;
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts;

/**
 * The service Prospecting offers the other contexts (ADR 0045, 0089): it refuses nothing, an unknown account has
 * no facts.
 */
interface ListOrganizationFactsService
{
    public function __invoke(ListOrganizationFacts $query, ListOrganizationFactsOutput $output): void;
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\ListContactFacts;

/**
 * The service PersonalData offers the other contexts (ADR 0045, 0089): it refuses nothing, an unknown account has
 * no facts.
 */
interface ListContactFactsService
{
    public function __invoke(ListContactFacts $query, ListContactFactsOutput $output): void;
}

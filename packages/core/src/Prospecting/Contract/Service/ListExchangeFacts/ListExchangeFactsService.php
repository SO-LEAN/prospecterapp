<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts;

/**
 * The service Prospecting offers the other contexts (ADR 0045, 0089): it refuses nothing, an unknown account has
 * no facts.
 */
interface ListExchangeFactsService
{
    public function __invoke(ListExchangeFacts $query, ListExchangeFactsOutput $output): void;
}

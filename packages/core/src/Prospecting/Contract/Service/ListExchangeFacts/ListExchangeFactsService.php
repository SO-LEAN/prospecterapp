<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts;

/**
 * A service Prospecting offers the other contexts (ADR 0045, 0089). It refuses nothing. An unknown account has no
 * facts.
 */
interface ListExchangeFactsService
{
    public function __invoke(ListExchangeFacts $query, ListExchangeFactsOutput $output): void;
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Port;

use Prospecter\Core\Reporting\Domain\Model\ExchangeFact;
use Prospecter\Core\Reporting\Domain\Model\OrganizationFact;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: the facts of the organizations and of the exchanges of an account as they are now, translated from
 * Prospecting, through its published services `ListOrganizationFactsService` and `ListExchangeFactsService`
 * (ADR 0045, 0089, 0099).
 */
interface ProspectingFacts
{
    /**
     * @return iterable<OrganizationFact>
     */
    public function organizationsOf(AccountId $accountId): iterable;

    /**
     * @return iterable<ExchangeFact>
     */
    public function exchangesOf(AccountId $accountId): iterable;
}

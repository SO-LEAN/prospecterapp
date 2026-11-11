<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ExchangeFactsResponse;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\KeptExchangeFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ListExchangeFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ListExchangeFactsService;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\KeptOrganizationFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\ListOrganizationFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\ListOrganizationFactsService;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\OrganizationFactsResponse;
use Prospecter\Core\Reporting\Domain\Model\ExchangeFact;
use Prospecter\Core\Reporting\Domain\Model\ExchangeId;
use Prospecter\Core\Reporting\Domain\Model\OrganizationFact;
use Prospecter\Core\Reporting\Domain\Port\ProspectingFacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089, 0099): the facts Prospecting gives again, slice
 * after slice, in the words of Reporting.
 */
final readonly class ProspectingFactsFromProspecting implements ProspectingFacts
{
    private const int SLICE = 100;

    public function __construct(private ListOrganizationFactsService $listOrganizationFactsService, private ListExchangeFactsService $listExchangeFactsService)
    {
    }

    public function organizationsOf(AccountId $accountId): iterable
    {
        $after = null;

        do {
            $kept = new KeptOrganizationFacts();
            ($this->listOrganizationFactsService)(new ListOrganizationFacts($accountId->value, $after, self::SLICE), $kept);

            yield from array_map(static fn (OrganizationFactsResponse $facts): OrganizationFact => new OrganizationFact($accountId, new OrganizationId($facts->organizationId), $facts->revision, $facts->present, $facts->located), $kept->facts);

            $after = $kept->next;
        } while (null !== $after);
    }

    public function exchangesOf(AccountId $accountId): iterable
    {
        $after = null;

        do {
            $kept = new KeptExchangeFacts();
            ($this->listExchangeFactsService)(new ListExchangeFacts($accountId->value, $after, self::SLICE), $kept);

            yield from array_map(static fn (ExchangeFactsResponse $facts): ExchangeFact => new ExchangeFact($accountId, new ExchangeId($facts->exchangeId), $facts->revision, $facts->present, $facts->notedAt), $kept->facts);

            $after = $kept->next;
        } while (null !== $after);
    }
}

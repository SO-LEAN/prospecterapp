<?php

declare(strict_types=1);

namespace App\Reporting\AntiCorruption;

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
use Prospecter\Core\Reporting\Domain\Model\OrganizationId;
use Prospecter\Core\Reporting\Domain\Port\ProspectingFacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0099). It lists the facts that Prospecting gives again, slice by slice, in
 * Reporting's terms.
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

            yield from array_map(static fn (ExchangeFactsResponse $facts): ExchangeFact => ExchangeFact::withStatus($accountId, new ExchangeId($facts->exchangeId), $facts->revision, $facts->present, $facts->status, $facts->notedAt), $kept->facts);

            $after = $kept->next;
        } while (null !== $after);
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\ExchangeDetailRetriever;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Port\CampaignNames;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads an exchange's detail. The campaign it was logged in has the name Campaigns gives it now (ADR 0145, 0152).
 */
final readonly class ConsultExchangeHandler implements QueryHandler
{
    public function __construct(
        private ExchangeDetailRetriever $exchangeDetailRetriever,
        private Prospectors $prospectors,
        private CampaignNames $campaignNames,
        private OrganizationSheetRetriever $organizationSheetRetriever,
    ) {
    }

    public function __invoke(ConsultExchange $query, ConsultExchangeOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $exchange = $this->exchangeDetailRetriever->get(new ExchangeId($query->id), $prospector, $query->asOf, $query->movedFrom);

        $output->present($this->withItsCampaignNamed($exchange, $prospector), $this->organizationItselfOf($exchange));
    }

    /**
     * An exchange with no contact is held with the organization itself. Stopping prospecting erases the organization
     * at once and its exchanges later: until then, the exchange is read without it.
     */
    private function organizationItselfOf(ExchangeDetail $exchange): ?OrganizationSheet
    {
        return null === $exchange->personId ? $this->organizationStillKnown($exchange->organizationIds[0]) : null;
    }

    private function organizationStillKnown(string $id): ?OrganizationSheet
    {
        try {
            return $this->organizationSheetRetriever->get(new OrganizationId($id));
        } catch (UnknownOrganization) {
            return null;
        }
    }

    private function withItsCampaignNamed(ExchangeDetail $exchange, Prospector $reader): ExchangeDetail
    {
        return null === $exchange->campaignId ? $exchange : $exchange->withCampaignNamed($this->campaignNames->of([new CampaignId($exchange->campaignId)], $reader->accountId));
    }
}

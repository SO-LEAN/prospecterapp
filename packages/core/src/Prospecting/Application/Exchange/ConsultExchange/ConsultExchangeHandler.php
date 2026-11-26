<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\ExchangeDetailRetriever;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Application\Port\PersonDescriptionRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
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
        private PersonDescriptionRetriever $personDescriptionRetriever,
        private OrganizationSheetRetriever $organizationSheetRetriever,
    ) {
    }

    public function __invoke(ConsultExchange $query, ConsultExchangeOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $exchange = $this->exchangeDetailRetriever->get(new ExchangeId($query->id), $prospector, $query->asOf, $query->movedFrom);

        $output->present($this->withItsCampaignNamed($exchange, $prospector), $this->participantsOf($exchange, $prospector));
    }

    /**
     * The exchange's contact and authors are read in one call. An exchange with no contact is kept for the organization
     * itself only. Stopping prospecting erases the organization at once and its exchanges later: until then, the
     * exchange is read without it.
     */
    private function participantsOf(ExchangeDetail $exchange, Prospector $reader): Participants
    {
        $contacts = null === $exchange->personId ? [] : [new ContactId($exchange->personId)];
        $authors = array_values(array_filter([$exchange->createdBy, $exchange->correctedBy, $exchange->madeConformBy]));
        $persons = $this->personDescriptionRetriever->shownTo($reader->id, $contacts, array_map(static fn (string $author): MemberId => new MemberId($author), $authors));

        return new Participants($persons, null === $exchange->personId ? $this->organizationStillKnown($exchange->organizationIds[0]) : null);
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

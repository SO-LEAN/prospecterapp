<?php

namespace App\Contacts;

use App\Contacts\Resource\PlannedExchangesResource;
use App\Prospecting\TimelineResourceFactory;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\KeptOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges\ConsultOverlappingExchanges;
use Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges\KeptOverlappingExchanges;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * The agenda of the signed-in member, composed of two contexts: Prospecting knows the exchanges they planned,
 * PersonalData names the persons they are with in one batch, Prospecting the organizations planned with alone; the
 * answer kept, then presented with them (ADR 0085).
 */
final readonly class AgendaReader
{
    public function __construct(
        private QueryHandler $consultOverlappingExchanges,
        private QueryHandler $consultPersons,
        private QueryHandler $consultOrganization,
        private TimelineResourceFactory $timelines,
    ) {
    }

    public function readOverlapping(string $holder, \DateTimeImmutable $startsAt, ?int $durationInMinutes): PlannedExchangesResource
    {
        $overlapping = new KeptOverlappingExchanges();
        ($this->consultOverlappingExchanges)(new ConsultOverlappingExchanges($holder, $startsAt, $durationInMinutes), $overlapping);
        $exchanges = $overlapping->overlapping->exchanges;
        $presenter = new OverlappingExchangesPresenter($this->personsOf($holder, $exchanges), $this->organizationsPlannedWithAlone($exchanges), $this->timelines);
        $presenter->present($overlapping->overlapping);

        return $presenter->resource;
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return array<string, PersonCard> by id
     */
    private function personsOf(string $reader, array $exchanges): array
    {
        $persons = new KeptPersons();
        ($this->consultPersons)(new ConsultPersons($reader, array_values(array_unique(array_filter(array_map(static fn (ExchangeInTimeline $exchange): ?string => $exchange->personId, $exchanges))))), $persons);

        return $persons->persons;
    }

    /**
     * Few: the exchanges overlapping a slot.
     *
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return array<string, OrganizationSheet> by id
     */
    private function organizationsPlannedWithAlone(array $exchanges): array
    {
        $ids = array_unique(array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->organizationIds[0], array_filter($exchanges, static fn (ExchangeInTimeline $exchange): bool => null === $exchange->personId)));

        return array_combine($ids, array_map($this->organization(...), $ids));
    }

    private function organization(string $id): OrganizationSheet
    {
        $kept = new KeptOrganization();
        ($this->consultOrganization)(new ConsultOrganization($id), $kept);

        return $kept->organization;
    }
}

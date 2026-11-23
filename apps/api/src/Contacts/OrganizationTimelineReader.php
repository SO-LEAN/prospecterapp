<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\TimelineResourceFactory;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\ConsultMailCatchUpIntents\ConsultMailCatchUpIntents;
use Prospecter\Core\Connectors\Application\ConsultMailCatchUpIntents\KeptMailCatchUpIntents;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ConsultExchangesToMakeConform\ConsultExchangesToMakeConform;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationTimeline\ConsultOrganizationTimeline;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationUpcomingExchanges\ConsultOrganizationUpcomingExchanges;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\KeptTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Reads an organization's timeline from the contexts (ADR 0076). Prospecting knows its exchanges and each exchange's
 * person. PersonalData names the slice's persons in one batch. Connectors says whether the e-mails exchanged with the
 * organization itself may be caught up. The reader keeps the slice, then presents it with them (ADR 0085). A command
 * returns nothing, so a controller calls this reader after it to answer with the fresh resource.
 */
final readonly class OrganizationTimelineReader
{
    public function __construct(
        private QueryHandler $consultOrganizationTimeline,
        private QueryHandler $consultExchangesToMakeConform,
        private QueryHandler $consultOrganizationUpcomingExchanges,
        private QueryHandler $consultPersons,
        private QueryHandler $consultMailCatchUpIntents,
        private TimelineResourceFactory $timelines,
        private Links $links,
        private IntentLinks $intentLinks,
        private ClockInterface $clock,
    ) {
    }

    public function read(string $reader, OrganizationSheet $organization, ?string $after = null): OrganizationTimelineResource
    {
        $timeline = new KeptTimeline();
        $this->consultOrganizationTimeline->__invoke(new ConsultOrganizationTimeline(prospectorId: $reader, organizationId: $organization->id, asOf: $this->clock->now(), after: $after), $timeline);
        $catchUp = new KeptMailCatchUpIntents();
        $this->consultMailCatchUpIntents->__invoke(ConsultMailCatchUpIntents::ofOrganization($reader, $organization->id), $catchUp);
        $presenter = OrganizationTimelinePresenter::ofTheTimeline($organization, $this->personsOf($reader, $timeline->timeline), $catchUp->possibleIntents, $this->timelines, $this->links, $this->intentLinks);
        $presenter->present($timeline->timeline);

        return $presenter->resource;
    }

    /**
     * Reads the exchanges to come, the soonest first.
     */
    public function readUpcoming(string $reader, OrganizationSheet $organization, ?string $after = null): OrganizationTimelineResource
    {
        $timeline = new KeptTimeline();
        $this->consultOrganizationUpcomingExchanges->__invoke(new ConsultOrganizationUpcomingExchanges(prospectorId: $reader, organizationId: $organization->id, asOf: $this->clock->now(), after: $after), $timeline);
        $presenter = OrganizationTimelinePresenter::ofWhatIsToCome($organization, $this->personsOf($reader, $timeline->timeline), $this->timelines, $this->links);
        $presenter->present($timeline->timeline);

        return $presenter->resource;
    }

    /**
     * Reads only the exchanges to redact, whose contact was forgotten (ADR 0079).
     */
    public function readToMakeConform(string $reader, OrganizationSheet $organization, ?string $after = null): OrganizationTimelineResource
    {
        $timeline = new KeptTimeline();
        $this->consultExchangesToMakeConform->__invoke(new ConsultExchangesToMakeConform($reader, $organization->id, $after), $timeline);
        $presenter = OrganizationTimelinePresenter::ofTheExchangesToMakeConform($organization, $this->personsOf($reader, $timeline->timeline), $this->timelines, $this->links);
        $presenter->present($timeline->timeline);

        return $presenter->resource;
    }

    /**
     * @return array<string, PersonCard> the persons the slice's exchanges were with, by id
     */
    private function personsOf(string $reader, Timeline $timeline): array
    {
        $persons = new KeptPersons();
        $this->consultPersons->__invoke(new ConsultPersons($reader, self::personIdsOf($timeline)), $persons);

        return $persons->persons;
    }

    /**
     * @return list<string> the persons the slice's exchanges were with, each once
     */
    private static function personIdsOf(Timeline $timeline): array
    {
        return array_values(array_unique(array_filter(array_map(static fn (ExchangeInTimeline $exchange): ?string => $exchange->personId, $timeline->exchanges))));
    }
}

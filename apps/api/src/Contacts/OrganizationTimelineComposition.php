<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationExchangeResource;
use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\TimelineResourceFactory;
use App\Security\IntentLinks;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Connectors\Application\MailImport\ConsultMailCatchUpIntents\ConsultMailCatchUpIntents;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultExchangesToMakeConform\ConsultExchangesToMakeConform;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultOrganizationTimeline\ConsultOrganizationTimeline;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultOrganizationUpcomingExchanges\ConsultOrganizationUpcomingExchanges;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Composes an organization's timeline (ADR 0076, 0217). Each read is one query of Prospecting, which gives the slice's
 * exchanges and their links. PersonalData names the persons the exchanges were with, joined by their ids. On the
 * timeline of the past, Connectors offers to catch up the e-mails exchanged with the organization itself. The person may still be a contact of
 * the organization, or may be removed or forgotten. A command returns nothing, so a controller calls this composition
 * after it to answer with the fresh resource.
 */
final readonly class OrganizationTimelineComposition
{
    public function __construct(
        private QueryHandler $consultOrganizationTimeline,
        private QueryHandler $consultExchangesToMakeConform,
        private QueryHandler $consultOrganizationUpcomingExchanges,
        private QueryHandler $consultMailCatchUpIntents,
        private TimelineResourceFactory $timelines,
        private PersonsOfAView $personsOfAView,
        private Links $links,
        private IntentLinks $intentLinks,
        private ClockInterface $clock,
    ) {
    }

    public function read(string $reader, OrganizationSheet $organization, ?string $after = null): OrganizationTimelineResource
    {
        $presenter = OrganizationTimelinePresenter::ofTheTimeline($this->timelines, $this->links);
        $this->consultOrganizationTimeline->__invoke(new ConsultOrganizationTimeline(prospectorId: $reader, organizationId: $organization->id, asOf: $this->clock->now(), after: $after), $presenter);
        $mail = new OrganizationMailPresenter($this->links, $this->intentLinks, $organization->id);
        $this->consultMailCatchUpIntents->__invoke(ConsultMailCatchUpIntents::ofOrganization($reader, $organization->id), $mail);

        return $this->withItsPersons($reader, $presenter, $mail->links->reimportGmail);
    }

    /**
     * Reads the exchanges to come, the soonest first.
     */
    public function readUpcoming(string $reader, OrganizationSheet $organization, ?string $after = null): OrganizationTimelineResource
    {
        $presenter = OrganizationTimelinePresenter::ofWhatIsToCome($this->timelines, $this->links);
        $this->consultOrganizationUpcomingExchanges->__invoke(new ConsultOrganizationUpcomingExchanges(prospectorId: $reader, organizationId: $organization->id, asOf: $this->clock->now(), after: $after), $presenter);

        return $this->withItsPersons($reader, $presenter);
    }

    /**
     * Reads only the exchanges to redact, whose contact was forgotten (ADR 0079).
     */
    public function readToMakeConform(string $reader, OrganizationSheet $organization, ?string $after = null): OrganizationTimelineResource
    {
        $presenter = OrganizationTimelinePresenter::ofTheExchangesToMakeConform($this->timelines, $this->links);
        $this->consultExchangesToMakeConform->__invoke(new ConsultExchangesToMakeConform($reader, $organization->id, $after), $presenter);

        return $this->withItsPersons($reader, $presenter);
    }

    private function withItsPersons(string $reader, OrganizationTimelinePresenter $presenter, ?LinkResource $reimportGmail = null): OrganizationTimelineResource
    {
        $organizationTimeline = $presenter->timeline;
        $exchanges = $organizationTimeline->timeline->exchanges;
        $persons = $this->personsOfAView->shownTo($reader, array_values(array_filter(array_map(static fn (ExchangeInTimeline $exchange): ?string => $exchange->personId, $exchanges))));

        return new OrganizationTimelineResource(
            items: array_map(static fn (ExchangeInTimeline $exchange): OrganizationExchangeResource => new OrganizationExchangeResource(
                $presenter->exchanges[$exchange->id],
                null === $exchange->personId
                    ? InterlocutorResource::ofTheOrganization($organizationTimeline->organization)
                    : InterlocutorResource::ofPerson($exchange->personId, $persons->contact($exchange->personId), $exchange->contactPresence),
            ), $exchanges),
            toRedact: $organizationTimeline->timeline->toMakeConform,
            links: $presenter->links->withReimportGmail($reimportGmail),
        );
    }
}

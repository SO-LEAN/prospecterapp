<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\TimelineResourceFactory;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultExchangesToMakeConform\ConsultExchangesToMakeConform;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultOrganizationTimeline\ConsultOrganizationTimeline;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultOrganizationUpcomingExchanges\ConsultOrganizationUpcomingExchanges;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Reads an organization's timeline (ADR 0076). Each read is one query of Prospecting, which names the slice's persons
 * and says whether the e-mails exchanged with the organization itself may be caught up, and a presenter built for the
 * call (ADR 0085, 0217). A command returns nothing, so a controller calls this reader after it to answer with the
 * fresh resource.
 */
final readonly class OrganizationTimelineReader
{
    public function __construct(
        private QueryHandler $consultOrganizationTimeline,
        private QueryHandler $consultExchangesToMakeConform,
        private QueryHandler $consultOrganizationUpcomingExchanges,
        private TimelineResourceFactory $timelines,
        private Links $links,
        private IntentLinks $intentLinks,
        private ClockInterface $clock,
    ) {
    }

    public function read(string $reader, OrganizationSheet $organization, ?string $after = null): OrganizationTimelineResource
    {
        $presenter = OrganizationTimelinePresenter::ofTheTimeline($this->timelines, $this->links, $this->intentLinks);
        $this->consultOrganizationTimeline->__invoke(new ConsultOrganizationTimeline(prospectorId: $reader, organizationId: $organization->id, asOf: $this->clock->now(), after: $after), $presenter);

        return $presenter->resource;
    }

    /**
     * Reads the exchanges to come, the soonest first.
     */
    public function readUpcoming(string $reader, OrganizationSheet $organization, ?string $after = null): OrganizationTimelineResource
    {
        $presenter = OrganizationTimelinePresenter::ofWhatIsToCome($this->timelines, $this->links);
        $this->consultOrganizationUpcomingExchanges->__invoke(new ConsultOrganizationUpcomingExchanges(prospectorId: $reader, organizationId: $organization->id, asOf: $this->clock->now(), after: $after), $presenter);

        return $presenter->resource;
    }

    /**
     * Reads only the exchanges to redact, whose contact was forgotten (ADR 0079).
     */
    public function readToMakeConform(string $reader, OrganizationSheet $organization, ?string $after = null): OrganizationTimelineResource
    {
        $presenter = OrganizationTimelinePresenter::ofTheExchangesToMakeConform($this->timelines, $this->links);
        $this->consultExchangesToMakeConform->__invoke(new ConsultExchangesToMakeConform($reader, $organization->id, $after), $presenter);

        return $presenter->resource;
    }
}

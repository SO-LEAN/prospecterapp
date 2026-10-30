<?php

namespace App\Contacts;

use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationExchangeResource;
use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\TimelineResourceFactory;
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\ExchangeResponse;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\TimelineOutput;
use Prospecter\Core\Prospecting\Application\TimelineResponse;

/**
 * A slice of the timeline of an organization, or of its exchanges to make conform, each exchange with who
 * communicated: the person, still its contact, removed or forgotten, or the organization itself. Built for one call
 * with the persons of the slice, it keeps the resource (ADR 0085).
 */
final class OrganizationTimelinePresenter implements TimelineOutput
{
    private const array ROUTES = ['api_organizations_timeline', 'api_organizations_note_exchange'];
    private const array TO_MAKE_CONFORM_ROUTES = ['api_organizations_exchanges_to_make_conform', 'api_organizations_note_exchange'];

    public private(set) OrganizationTimelineResource $resource;

    /**
     * @param array{string, string}         $routes  the route of the list and the route noting an exchange
     * @param array<string, PersonResponse> $persons the persons of the slice, by id
     */
    private function __construct(
        private readonly array $routes,
        private readonly OrganizationSheet $organization,
        private readonly array $persons,
        private readonly TimelineResourceFactory $timelineResourceFactory,
        private readonly Links $links,
    ) {
    }

    /**
     * @param array<string, PersonResponse> $persons the persons of the slice, by id
     */
    public static function ofTheTimeline(OrganizationSheet $organization, array $persons, TimelineResourceFactory $timelines, Links $links): self
    {
        return new self(self::ROUTES, $organization, $persons, $timelines, $links);
    }

    /**
     * @param array<string, PersonResponse> $persons the persons of the slice, by id
     */
    public static function ofTheExchangesToMakeConform(OrganizationSheet $organization, array $persons, TimelineResourceFactory $timelines, Links $links): self
    {
        return new self(self::TO_MAKE_CONFORM_ROUTES, $organization, $persons, $timelines, $links);
    }

    public function present(TimelineResponse $timeline): void
    {
        $subject = ['id' => $this->organization->id];

        $this->resource = new OrganizationTimelineResource(
            items: array_map(fn (ExchangeResponse $exchange): OrganizationExchangeResource => new OrganizationExchangeResource($this->timelineResourceFactory->createExchange($exchange), $this->interlocutorOf($exchange)), $timeline->exchanges),
            toMakeConform: $timeline->toMakeConform,
            links: $this->timelineResourceFactory->createLinks($this->routes, $subject, $timeline)->withToMakeConform(0 === $timeline->toMakeConform ? null : $this->links->to(self::TO_MAKE_CONFORM_ROUTES[0], $subject)),
        );
    }

    private function interlocutorOf(ExchangeResponse $exchange): InterlocutorResource
    {
        return null === $exchange->personId
            ? InterlocutorResource::ofTheOrganization($this->organization)
            : InterlocutorResource::ofPerson($exchange->personId, $this->persons[$exchange->personId] ?? null, $exchange->contactPresence);
    }
}

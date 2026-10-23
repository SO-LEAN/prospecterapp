<?php

namespace App\Contacts;

use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationExchangeResource;
use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\Presenter\TimelinePresenter;
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\ExchangeResponse;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Application\TimelineResponse;

/**
 * A slice of the timeline of an organization, or of its exchanges to make conform, each exchange with who
 * communicated: the person, still its contact, removed or forgotten, or the organization itself.
 */
final readonly class OrganizationTimelinePresenter
{
    private const array ROUTES = ['api_organizations_timeline', 'api_organizations_note_exchange'];
    private const array TO_MAKE_CONFORM_ROUTES = ['api_organizations_exchanges_to_make_conform', 'api_organizations_note_exchange'];

    public function __construct(private TimelinePresenter $timelines, private Links $links)
    {
    }

    /**
     * @param array<string, PersonResponse> $persons the persons of the slice, by id
     */
    public function present(OrganizationResponse $organization, TimelineResponse $timeline, array $persons): OrganizationTimelineResource
    {
        return $this->presentWith(self::ROUTES, $organization, $timeline, $persons);
    }

    /**
     * @param array<string, PersonResponse> $persons the persons of the slice, by id
     */
    public function presentToMakeConform(OrganizationResponse $organization, TimelineResponse $timeline, array $persons): OrganizationTimelineResource
    {
        return $this->presentWith(self::TO_MAKE_CONFORM_ROUTES, $organization, $timeline, $persons);
    }

    /**
     * @param array{string, string}         $routes  the route of the list and the route noting an exchange
     * @param array<string, PersonResponse> $persons
     */
    private function presentWith(array $routes, OrganizationResponse $organization, TimelineResponse $timeline, array $persons): OrganizationTimelineResource
    {
        $subject = ['id' => $organization->id];

        return new OrganizationTimelineResource(
            items: array_map(fn (ExchangeResponse $exchange): OrganizationExchangeResource => new OrganizationExchangeResource($this->timelines->presentExchange($exchange), self::interlocutorOf($exchange, $organization, $persons)), $timeline->exchanges),
            toMakeConform: $timeline->toMakeConform,
            links: $this->timelines->linksOf($routes, $subject, $timeline)->withToMakeConform(0 === $timeline->toMakeConform ? null : $this->links->to(self::TO_MAKE_CONFORM_ROUTES[0], $subject)),
        );
    }

    /**
     * @param array<string, PersonResponse> $persons
     */
    private static function interlocutorOf(ExchangeResponse $exchange, OrganizationResponse $organization, array $persons): InterlocutorResource
    {
        return null === $exchange->personId
            ? InterlocutorResource::ofTheOrganization($organization)
            : InterlocutorResource::ofPerson($exchange->personId, $persons[$exchange->personId] ?? null, $exchange->contactPresence);
    }
}

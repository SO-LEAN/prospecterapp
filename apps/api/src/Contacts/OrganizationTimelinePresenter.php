<?php

namespace App\Contacts;

use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationExchangeResource;
use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\Presenter\TimelinePresenter;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\ExchangeResponse;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Application\TimelineResponse;

/**
 * A slice of the timeline of an organization, each exchange with who communicated: the person, or the
 * organization itself.
 */
final readonly class OrganizationTimelinePresenter
{
    private const array ROUTES = ['api_organizations_timeline', 'api_organizations_note_exchange'];

    public function __construct(private TimelinePresenter $timelines)
    {
    }

    /**
     * @param array<string, PersonResponse> $persons the persons of the slice, by id
     */
    public function present(OrganizationResponse $organization, TimelineResponse $timeline, array $persons): OrganizationTimelineResource
    {
        return new OrganizationTimelineResource(
            items: array_map(fn (ExchangeResponse $exchange): OrganizationExchangeResource => new OrganizationExchangeResource($this->timelines->presentExchange($exchange), self::interlocutorOf($exchange, $organization, $persons)), $timeline->exchanges),
            links: $this->timelines->linksOf(self::ROUTES, ['id' => $organization->id], $timeline),
        );
    }

    /**
     * @param array<string, PersonResponse> $persons
     */
    private static function interlocutorOf(ExchangeResponse $exchange, OrganizationResponse $organization, array $persons): InterlocutorResource
    {
        return null === $exchange->personId
            ? InterlocutorResource::ofTheOrganization($organization)
            : InterlocutorResource::ofPerson($exchange->personId, $persons[$exchange->personId] ?? null);
    }
}

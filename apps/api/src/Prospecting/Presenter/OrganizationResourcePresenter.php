<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\OrganizationLinksResource;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use App\Shared\Resource\AddressResource;
use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\LegalIdentityResource;
use App\Shared\Resource\LinkResource;
use App\Shared\Resource\LocationResource;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganizationOutput;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;

/**
 * An organization with a link per intention its state offers and the signed-in member may carry out
 * (`IntentLinks`, ADR 0081), and to its timeline (ADR 0076); removing a contact concerns one contact, so its link is on
 * each contact of GET /api/organizations/{id}/contacts. Each link names its intention, its route, and the role its
 * controller requires beyond reaching the organization. Built for one call, it keeps the resource (ADR 0085).
 */
final class OrganizationResourcePresenter implements ConsultOrganizationOutput
{
    /**
     * @var array<string, array{intent: string, route: string, role: ?string}>
     */
    private const array ROUTES = [
        'reviseLegalIdentity' => ['intent' => 'ReviseLegalIdentity', 'route' => 'api_organizations_revise_legal_identity', 'role' => null],
        'changeContactDetails' => ['intent' => 'ChangeContactDetails', 'route' => 'api_organizations_change_contact_details', 'role' => null],
        'relocate' => ['intent' => 'Relocate', 'route' => 'api_organizations_relocate', 'role' => null],
        'writeObservations' => ['intent' => 'WriteObservations', 'route' => 'api_organizations_write_observations', 'role' => null],
        'attachToHolding' => ['intent' => 'AttachToHolding', 'route' => 'api_organizations_attach_to_holding', 'role' => null],
        'detachFromHolding' => ['intent' => 'DetachFromHolding', 'route' => 'api_organizations_detach_from_holding', 'role' => null],
        'changeLogo' => ['intent' => 'ChangeLogo', 'route' => 'api_organizations_change_logo', 'role' => null],
        'removeLogo' => ['intent' => 'RemoveLogo', 'route' => 'api_organizations_remove_logo', 'role' => null],
        'addContact' => ['intent' => 'AddContact', 'route' => 'api_organizations_add_contact', 'role' => User::PERSONAL_DATA],
    ];

    /**
     * Every member reaching the organization reads its timeline; noting in it needs the right to manage personal data.
     *
     * @var array<string, array{route: string, role: ?string}>
     */
    private const array TIMELINE_ROUTES = [
        'exchanges' => ['route' => 'api_organizations_timeline', 'role' => null],
        'noteExchange' => ['route' => 'api_organizations_note_exchange', 'role' => User::PERSONAL_DATA],
    ];

    public private(set) OrganizationResource $resource;

    public function __construct(private readonly ImageResourceFactory $imageResourceFactory, private readonly Links $links, private readonly IntentLinks $intentLinks)
    {
    }

    public function present(OrganizationResponse $organization): void
    {
        $this->resource = $this->resourceOf($organization);
    }

    private function resourceOf(OrganizationResponse $response): OrganizationResource
    {
        return new OrganizationResource(
            id: $response->id,
            name: $response->fullName,
            legalIdentity: new LegalIdentityResource($response->corporateName, $response->form, $response->type),
            contactDetails: new ContactDetailsResource($response->email, $response->phoneNumber, $response->language),
            address: AddressResource::orNone($response->street, $response->postalCode, $response->city, $response->country),
            location: LocationResource::orNone($response->latitude, $response->longitude),
            observations: $response->observations,
            logo: $this->imageResourceFactory->create($response->logo?->url),
            holdingId: $response->holdingId,
            contactIds: $response->contactIds,
            createdAt: $response->createdAt,
            modifiedAt: $response->modifiedAt,
            links: $this->linksOf($response),
        );
    }

    private function linksOf(OrganizationResponse $response): OrganizationLinksResource
    {
        $id = ['id' => $response->id];
        $granted = [
            ...array_map(
                static fn (array $link): string => $link['route'],
                array_filter(self::ROUTES, fn (array $link): bool => $this->intentLinks->shows($link['intent'], $response->possibleIntents, $link['role'])),
            ),
            ...array_map(
                static fn (array $link): string => $link['route'],
                array_filter(self::TIMELINE_ROUTES, fn (array $link): bool => $this->intentLinks->holds($link['role'])),
            ),
            ...($this->mayStopProspecting($response) ? ['stopProspecting' => 'api_organizations_stop_prospecting'] : []),
        ];

        return new OrganizationLinksResource(
            $this->links->to('api_organizations_show', $id),
            ...array_map(fn (string $route): LinkResource => $this->links->to($route, $id), $granted),
        );
    }

    /**
     * Stopping to prospect an organization erases its contacts: the right to manage them is needed while it has some,
     * as its controller checks.
     */
    private function mayStopProspecting(OrganizationResponse $response): bool
    {
        return $this->intentLinks->shows('StopProspecting', $response->possibleIntents, [] === $response->contactIds ? null : User::PERSONAL_DATA);
    }
}

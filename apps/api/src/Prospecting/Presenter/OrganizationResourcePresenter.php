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
use Prospecter\Core\Prospecting\Application\OrganizationResponse;

/**
 * An organization with a link per intention its state offers and the signed-in member may carry out
 * (`IntentLinks`, ADR 0081), and to its timeline (ADR 0076); removing a contact concerns one contact, so its link is on
 * each contact of GET /api/organizations/{id}/contacts. Each link names its intention, its route, and the role its
 * controller requires beyond reaching the organization.
 */
final readonly class OrganizationResourcePresenter
{
    /**
     * Stopping to prospect an organization erases its contacts: the right to manage them is needed while it has some.
     */
    private const string PERSONAL_DATA_WITH_CONTACTS = 'personalDataWithContacts';

    private const array ROUTES = [
        'reviseLegalIdentity' => ['ReviseLegalIdentity', 'api_organizations_revise_legal_identity', null],
        'changeContactDetails' => ['ChangeContactDetails', 'api_organizations_change_contact_details', null],
        'relocate' => ['Relocate', 'api_organizations_relocate', null],
        'writeObservations' => ['WriteObservations', 'api_organizations_write_observations', null],
        'attachToHolding' => ['AttachToHolding', 'api_organizations_attach_to_holding', null],
        'detachFromHolding' => ['DetachFromHolding', 'api_organizations_detach_from_holding', null],
        'changeLogo' => ['ChangeLogo', 'api_organizations_change_logo', null],
        'removeLogo' => ['RemoveLogo', 'api_organizations_remove_logo', null],
        'addContact' => ['AddContact', 'api_organizations_add_contact', User::PERSONAL_DATA],
        'stopProspecting' => ['StopProspecting', 'api_organizations_stop_prospecting', self::PERSONAL_DATA_WITH_CONTACTS],
    ];

    /**
     * Every member reaching the organization reads its timeline; noting in it needs the right to manage personal data.
     */
    private const array TIMELINE_ROUTES = [
        'exchanges' => ['api_organizations_timeline', null],
        'noteExchange' => ['api_organizations_note_exchange', User::PERSONAL_DATA],
    ];

    public function __construct(private ImageResourceFactory $images, private Links $links, private IntentLinks $intents)
    {
    }

    public function present(OrganizationResponse $response): OrganizationResource
    {
        return new OrganizationResource(
            id: $response->id,
            name: $response->fullName,
            legalIdentity: new LegalIdentityResource($response->corporateName, $response->form, $response->type),
            contactDetails: new ContactDetailsResource($response->email, $response->phoneNumber, $response->language),
            address: AddressResource::orNone($response->street, $response->postalCode, $response->city, $response->country),
            location: LocationResource::orNone($response->latitude, $response->longitude),
            observations: $response->observations,
            logo: $this->images->create($response->logo?->url),
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
                static fn (array $route): string => $route[1],
                array_filter(self::ROUTES, fn (array $route): bool => $this->intents->shows($route[0], $response->possibleIntents, $this->roleOf($route[2], $response))),
            ),
            ...array_map(
                static fn (array $route): string => $route[0],
                array_filter(self::TIMELINE_ROUTES, fn (array $route): bool => $this->intents->holds($route[1])),
            ),
        ];

        return new OrganizationLinksResource(
            $this->links->to('api_organizations_show', $id),
            ...array_map(fn (string $route): LinkResource => $this->links->to($route, $id), $granted),
        );
    }

    private function roleOf(?string $role, OrganizationResponse $response): ?string
    {
        if (self::PERSONAL_DATA_WITH_CONTACTS !== $role) {
            return $role;
        }

        return [] === $response->contactIds ? null : User::PERSONAL_DATA;
    }
}

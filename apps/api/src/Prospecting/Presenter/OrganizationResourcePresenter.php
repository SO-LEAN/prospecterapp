<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\OrganizationLinksResource;
use App\Prospecting\Resource\OrganizationResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use App\Shared\Resource\AddressResource;
use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\LegalIdentityResource;
use App\Shared\Resource\LocationResource;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;

/**
 * An organization with a link per intention it offers; removing a contact concerns one contact, so its link is
 * on each contact of GET /api/organizations/{id}/contacts.
 */
final readonly class OrganizationResourcePresenter
{
    private const array ROUTES = [
        'reviseLegalIdentity' => 'api_organizations_revise_legal_identity',
        'changeContactDetails' => 'api_organizations_change_contact_details',
        'relocate' => 'api_organizations_relocate',
        'writeObservations' => 'api_organizations_write_observations',
        'attachToHolding' => 'api_organizations_attach_to_holding',
        'detachFromHolding' => 'api_organizations_detach_from_holding',
        'changeLogo' => 'api_organizations_change_logo',
        'removeLogo' => 'api_organizations_remove_logo',
        'addContact' => 'api_organizations_add_contact',
        'stopProspecting' => 'api_organizations_stop_prospecting',
    ];

    public function __construct(private ImageResourceFactory $images, private Links $links)
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
        $possible = [];
        foreach ($response->possibleIntents as $intent) {
            $name = lcfirst($intent);

            if (isset(self::ROUTES[$name])) {
                $possible[$name] = $this->links->to(self::ROUTES[$name], $id);
            }
        }

        return new OrganizationLinksResource($this->links->to('api_organizations_show', $id), ...$possible);
    }
}

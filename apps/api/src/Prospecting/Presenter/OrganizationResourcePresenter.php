<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\AddressResource;
use App\Prospecting\Resource\LocationResource;
use App\Prospecting\Resource\OrganizationLinksResource;
use App\Prospecting\Resource\OrganizationResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\LegalIdentityResource;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;

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
    ];

    public function __construct(private ImageResourceFactory $images, private Links $links)
    {
    }

    public function present(OrganizationResponse $response): OrganizationResource
    {
        return new OrganizationResource(
            id: $response->id->value,
            name: $response->fullName,
            legalIdentity: LegalIdentityResource::from($response->legalIdentity),
            contactDetails: ContactDetailsResource::from($response->contactDetails),
            address: AddressResource::from($response->address),
            location: LocationResource::from($response->geoPoint),
            observations: $response->observations,
            logo: $this->images->create($response->logo?->url),
            holdingId: $response->holdingId?->value,
            links: $this->linksOf($response),
        );
    }

    private function linksOf(OrganizationResponse $response): OrganizationLinksResource
    {
        $id = ['id' => $response->id->value];
        $possible = [];
        foreach ($response->possibleIntents as $intent) {
            $name = lcfirst($intent->name);
            $possible[$name] = $this->links->to(self::ROUTES[$name], $id);
        }

        return new OrganizationLinksResource($this->links->to('api_organizations_show', $id), ...$possible);
    }
}

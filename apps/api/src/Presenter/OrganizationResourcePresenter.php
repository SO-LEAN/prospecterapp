<?php

namespace App\Presenter;

use App\Api\ImageResourceFactory;
use App\Api\Resource\AddressResource;
use App\Api\Resource\ContactDetailsResource;
use App\Api\Resource\LegalIdentityResource;
use App\Api\Resource\LocationResource;
use App\Api\Resource\OrganizationResource;
use Prospecter\Core\Application\Organization\OrganizationPresenter;
use Prospecter\Core\Application\Organization\OrganizationResponse;

final readonly class OrganizationResourcePresenter implements OrganizationPresenter
{
    public function __construct(private ImageResourceFactory $images)
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
            activeOrganizations: $response->activeOrganizations,
        );
    }
}

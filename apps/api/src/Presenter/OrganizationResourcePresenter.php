<?php

namespace App\Presenter;

use App\Api\ImageResourceFactory;
use App\Api\Resource\AddressResource;
use App\Api\Resource\LocationResource;
use App\Api\Resource\OrganizationResource;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationPresenter;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationResponse;

final readonly class OrganizationResourcePresenter implements GetOrganizationPresenter
{
    public function __construct(private ImageResourceFactory $images)
    {
    }

    public function present(GetOrganizationResponse $response): OrganizationResource
    {
        return new OrganizationResource(
            id: $response->id,
            corporateName: $response->corporateName,
            form: $response->form,
            type: $response->type,
            email: $response->email,
            phoneNumber: $response->phoneNumber,
            language: $response->language,
            address: AddressResource::from($response->address),
            location: LocationResource::from($response->geoPoint),
            observations: $response->observations,
            logo: $this->images->create($response->logo?->url),
            holdBy: $response->holdBy,
        );
    }
}

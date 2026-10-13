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
        $hasAddress = null !== $response->getStreet() || null !== $response->getPostalCode() || null !== $response->getCity() || null !== $response->getCountry();

        return new OrganizationResource(
            id: (int) $response->getId(),
            corporateName: $response->getCorporateName(),
            form: $response->getForm(),
            type: $response->getType(),
            email: $response->getEmail(),
            phoneNumber: $response->getPhoneNumber(),
            language: $response->getLanguage(),
            address: $hasAddress ? new AddressResource($response->getStreet(), $response->getPostalCode(), $response->getCity(), $response->getCountry()) : null,
            location: LocationResource::fromCoordinates($response->getLatitude(), $response->getLongitude()),
            observations: $response->getObservations(),
            logo: $this->images->create($response->getLogoUrl()),
            holdBy: null === $response->getHoldBy() ? null : (int) $response->getHoldBy(),
        );
    }
}

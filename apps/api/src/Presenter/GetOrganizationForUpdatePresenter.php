<?php

namespace App\Presenter;

use Prospecter\Core\UseCase\GetOrganization\GetOrganizationPresenter;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationResponse;

class GetOrganizationForUpdatePresenter implements GetOrganizationPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(GetOrganizationResponse $response): array
    {
        return [
            'id' => $response->getId(),
            'ownedBy' => $response->getOwnedBy(),
            'phoneNumber' => $response->getPhoneNumber(),
            'email' => $response->getEmail(),
            'language' => $response->getLanguage(),
            'corporateName' => $response->getCorporateName(),
            'form' => $response->getForm(),
            'type' => $response->getType(),
            'street' => $response->getStreet(),
            'postalCode' => $response->getPostalCode(),
            'city' => $response->getCity(),
            'country' => $response->getCountry(),
            'observations' => $response->getObservations(),
            'logoUrl' => $response->getLogoUrl(),
            'logoExtension' => $response->getLogoExtension(),
            'logoSize' => $response->getLogoSize(),
            'holdBy' => $response->getHoldBy(),
        ];
    }
}

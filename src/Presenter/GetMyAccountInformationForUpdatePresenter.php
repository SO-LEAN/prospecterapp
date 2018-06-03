<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationResponse;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationPresenter;

class GetOrganizationForUpdatePresenter implements GetOrganizationPresenter
{
    /**
     * @param GetOrganizationResponse $response
     *
     * @return array
     */
    public function present(GetOrganizationResponse $response)
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

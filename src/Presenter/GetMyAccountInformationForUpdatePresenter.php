<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\GetMyAccountInformation\GetMyAccountInformationResponse;
use Solean\CleanProspecter\UseCase\GetMyAccountInformation\GetMyAccountInformationPresenter;

class GetMyAccountInformationForUpdatePresenter implements GetMyAccountInformationPresenter
{
    /**
     * @param GetMyAccountInformationResponse $response
     *
     * @return array
     */
    public function present(GetMyAccountInformationResponse $response)
    {
        return [
            'userName' => $response->getUserName(),
            'firstName' => $response->getFirstName(),
            'lastName' => $response->getLastName(),
            'email' => $response->getEmail(),
            'phoneNumber' => $response->getPhoneNumber(),
            'language' => $response->getLanguage(),
            'pictureUrl' => $response->getPictureUrl(),
            'pictureExtension' => $response->getPictureExtension(),
            'pictureSize' => $response->getPictureSize(),
            'organizationCorporateName' => $response->getOrganizationCorporateName(),
            'organizationForm' => $response->GetOrganizationForm(),
            'organizationLogoUrl' => $response->getOrganizationLogoUrl(),
            'organizationLogoExtension' => $response->getOrganizationLogoExtension(),
            'organizationLogoSize' => $response->getOrganizationLogoSize(),
        ];
    }
}

<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\GetMyAccountInformation\GetMyAccountInformationPresenter;
use Solean\CleanProspecter\UseCase\GetMyAccountInformation\GetMyAccountInformationResponse;

class GetMyAccountInformationForUpdatePresenter implements GetMyAccountInformationPresenter
{
    /**
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

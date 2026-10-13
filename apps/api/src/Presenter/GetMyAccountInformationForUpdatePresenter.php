<?php

namespace App\Presenter;

use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationPresenter;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationResponse;

class GetMyAccountInformationForUpdatePresenter implements GetMyAccountInformationPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(GetMyAccountInformationResponse $response): array
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

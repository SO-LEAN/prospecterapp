<?php

namespace App\Presenter;

use App\Api\ImageResourceFactory;
use App\Api\Resource\AccountOrganizationResource;
use App\Api\Resource\AccountResource;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationPresenter;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationResponse;

final readonly class AccountResourcePresenter implements GetMyAccountInformationPresenter
{
    public function __construct(private ImageResourceFactory $images)
    {
    }

    public function present(GetMyAccountInformationResponse $response): AccountResource
    {
        return new AccountResource(
            userName: $response->getUserName(),
            firstName: $response->getFirstName(),
            lastName: $response->getLastName(),
            email: $response->getEmail(),
            phoneNumber: $response->getPhoneNumber(),
            language: $response->getLanguage(),
            picture: $this->images->create($response->getPictureUrl()),
            organization: new AccountOrganizationResource(
                $response->getOrganizationCorporateName(),
                $response->getOrganizationForm(),
                $this->images->create($response->getOrganizationLogoUrl()),
            ),
        );
    }
}

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
            userName: $response->userName,
            firstName: $response->firstName,
            lastName: $response->lastName,
            email: $response->email,
            phoneNumber: $response->phoneNumber,
            language: $response->language,
            picture: $this->images->create($response->picture?->url),
            organization: new AccountOrganizationResource(
                $response->organizationCorporateName,
                $response->organizationForm,
                $this->images->create($response->organizationLogo?->url),
            ),
        );
    }
}

<?php

namespace App\Presenter;

use App\Api\ImageResourceFactory;
use App\Api\Resource\AccountOrganizationResource;
use App\Api\Resource\AccountResource;
use App\Api\Resource\ContactDetailsResource;
use App\Api\Resource\LegalIdentityResource;
use App\Api\Resource\PersonNameResource;
use Prospecter\Core\Application\Account\AccountPresenter;
use Prospecter\Core\Application\Account\AccountResponse;

final readonly class AccountResourcePresenter implements AccountPresenter
{
    public function __construct(private ImageResourceFactory $images)
    {
    }

    public function present(AccountResponse $response): AccountResource
    {
        return new AccountResource(
            userName: $response->userName,
            name: PersonNameResource::from($response->name),
            contactDetails: ContactDetailsResource::from($response->contactDetails),
            picture: $this->images->create($response->picture?->url),
            organization: new AccountOrganizationResource(
                LegalIdentityResource::from($response->organizationIdentity),
                $this->images->create($response->organizationLogo?->url),
            ),
        );
    }
}

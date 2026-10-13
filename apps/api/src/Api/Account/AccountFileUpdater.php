<?php

namespace App\Api\Account;

use App\Api\Resource\AccountResource;
use App\Entity\User;
use App\Presenter\AccountResourcePresenter;
use App\Presenter\UpdateMyAccountInformationPresenterImpl;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationRequest;
use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationRequest;
use Prospecter\Core\UseCase\UseCasesFacade;

/**
 * Stores a new picture or organization logo: the use case replaces every account field,
 * so the current values are sent back along with the file.
 */
final readonly class AccountFileUpdater
{
    public function __construct(
        private UseCasesFacade $useCases,
        private AccountResourcePresenter $presenter,
    ) {
    }

    public function update(User $user, ?\SplFileInfo $picture = null, ?\SplFileInfo $organizationLogo = null): void
    {
        /** @var AccountResource $account */
        $account = $this->useCases->getMyAccountInformation(new GetMyAccountInformationRequest(), $this->presenter, $user);

        $this->useCases->updateMyAccountInformation(new UpdateMyAccountInformationRequest(
            userName: $account->userName,
            password: null,
            firstName: $account->firstName,
            lastName: $account->lastName,
            picture: $picture,
            phoneNumber: $account->phoneNumber,
            email: $account->email,
            language: $account->language,
            organizationCorporateName: $account->organization->corporateName,
            organizationForm: $account->organization->form,
            organizationLogo: $organizationLogo,
        ), new UpdateMyAccountInformationPresenterImpl(), $user);
    }
}

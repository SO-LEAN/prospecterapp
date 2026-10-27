<?php

namespace App\Me;

use App\Me\Resource\AccountResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\KeptAccount;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * My account as the API shows it: my membership read in Accounts, my profile read in PersonalData under my
 * person id, presented to the presenter it builds for the call (ADR 0085); what I sign in with is read apart
 * (`/api/me/sign-in`). A controller calls it after a command, which returns nothing, to answer with the fresh
 * resource.
 */
final readonly class MyAccountReader
{
    public function __construct(
        private RefusalTranslatingHandler $consultMyAccount,
        private RefusalTranslatingHandler $consultPerson,
        private ImageResourceFactory $images,
        private Links $links,
    ) {
    }

    public function read(string $me): AccountResource
    {
        $account = new KeptAccount();
        ($this->consultMyAccount)(new ConsultMyAccount($me), $account);
        $presenter = new AccountResourcePresenter($account->account, $this->images, $this->links);
        ($this->consultPerson)(new ConsultPerson($me, $account->account->personId), $presenter);

        return $presenter->resource;
    }
}

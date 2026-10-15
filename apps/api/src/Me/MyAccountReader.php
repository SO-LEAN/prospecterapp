<?php

namespace App\Me;

use App\Me\Resource\AccountResource;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * My account as the API shows it: my membership read in Accounts, my profile read in PersonalData under my
 * person id, then presented; what I sign in with is read on the authorization server. A controller calls it after a command, which returns nothing, to answer with
 * the fresh resource.
 */
final readonly class MyAccountReader
{
    public function __construct(
        private RefusalTranslatingHandler $consultMyAccount,
        private RefusalTranslatingHandler $consultPerson,
        private AccountResourcePresenter $presenter,
    ) {
    }

    public function read(string $me): AccountResource
    {
        $account = ($this->consultMyAccount)(new ConsultMyAccount($me));

        return $this->presenter->present($account, ($this->consultPerson)(new ConsultPerson($me, $account->personId)));
    }
}

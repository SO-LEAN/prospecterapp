<?php

namespace App\Me;

use App\Auth\LoginEmails;
use App\Me\Resource\AccountResource;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccountHandler;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPersonHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * My account as the API shows it: my membership read in Accounts, my profile read in PersonalData under my
 * person id, my login email kept by authentication, then presented. A controller calls it after a command, which returns nothing, to answer with
 * the fresh resource.
 */
final readonly class MyAccountReader
{
    public function __construct(
        private ConsultMyAccountHandler $consultMyAccount,
        private ConsultPersonHandler $consultPerson,
        private LoginEmails $loginEmails,
        private AccountResourcePresenter $presenter,
    ) {
    }

    public function read(MemberId $me): AccountResource
    {
        $account = ($this->consultMyAccount)(new ConsultMyAccount($me));

        return $this->presenter->present($account, ($this->consultPerson)(new ConsultPerson($me, $account->personId)), $this->loginEmails->of($me));
    }
}

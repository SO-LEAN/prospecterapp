<?php

namespace App\Accounts;

use App\Accounts\Presenter\AccountResourcePresenter;
use App\Accounts\Resource\AccountResource;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccountHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * My account as the API shows it: read by the query of the core, then presented. A controller calls it
 * after a command, which returns nothing, to answer with the fresh resource.
 */
final readonly class AccountReader
{
    public function __construct(
        private ConsultMyAccountHandler $consultMyAccount,
        private AccountResourcePresenter $presenter,
    ) {
    }

    public function read(MemberId $me): AccountResource
    {
        return $this->presenter->present(($this->consultMyAccount)(new ConsultMyAccount($me)));
    }
}

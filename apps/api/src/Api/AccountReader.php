<?php

namespace App\Api;

use App\Api\Resource\AccountResource;
use App\Presenter\AccountResourcePresenter;
use Prospecter\Core\Application\Account\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Application\Account\ConsultMyAccount\ConsultMyAccountHandler;
use Prospecter\Core\Domain\User\UserId;

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

    public function read(UserId $me): AccountResource
    {
        return $this->presenter->present(($this->consultMyAccount)(new ConsultMyAccount($me)));
    }
}

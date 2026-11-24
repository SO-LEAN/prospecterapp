<?php

declare(strict_types=1);

namespace App\Me;

use App\Me\Resource\AccountResource;
use App\Security\IntentLinks;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use Prospecter\Core\Accounts\Application\Account\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Accounts\Application\Account\ConsultMyAccount\KeptAccount;
use Prospecter\Core\PersonalData\Application\Person\ConsultPerson\ConsultPerson;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Reads my account as the API shows it. It reads my membership in Accounts and my profile in PersonalData, under my
 * person id. It gives them to the presenter that it builds for the call (ADR 0085). What I sign in with is read
 * separately (`/api/me/sign-in`). A command returns nothing, so a controller calls this reader after a command to
 * answer with the fresh resource.
 */
final readonly class MyAccountReader
{
    public function __construct(
        private QueryHandler $consultMyAccount,
        private QueryHandler $consultPerson,
        private ImageResourceFactory $images,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function read(string $me): AccountResource
    {
        $account = new KeptAccount();
        $this->consultMyAccount->__invoke(new ConsultMyAccount($me), $account);
        $presenter = new AccountResourcePresenter($account->account, $this->images, $this->links, $this->intentLinks);
        $this->consultPerson->__invoke(new ConsultPerson($me, $account->account->personId), $presenter);

        return $presenter->resource;
    }
}

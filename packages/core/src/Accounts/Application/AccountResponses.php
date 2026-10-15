<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application;

use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\Member;

final readonly class AccountResponses
{
    public function __construct(private AccountRepository $accounts)
    {
    }

    public function of(Member $me): AccountResponse
    {
        $myAccount = $this->accounts->get($me->accountId());

        return new AccountResponse(
            userName: $me->userName(),
            name: $me->name(),
            contactDetails: $me->contactDetails(),
            picture: $me->picture(),
            possibleIntents: $me->possibleIntents(),
            organizationIdentity: $myAccount->legalIdentity(),
            organizationLogo: $myAccount->logo(),
            accountIntents: $myAccount->possibleIntentsOf($me),
        );
    }
}

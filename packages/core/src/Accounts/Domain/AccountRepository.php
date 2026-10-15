<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;

interface AccountRepository
{
    /**
     * @throws UnknownAccount
     */
    public function get(AccountId $id): Account;

    /**
     * @throws EmailAlreadyUsed
     */
    public function save(Account $account): void;
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\EmailAlreadyUsed;

interface AccountRepository
{
    public function nextIdentity(): AccountId;

    /**
     * @throws UnknownAccount
     */
    public function get(AccountId $id): Account;

    /**
     * @throws EmailAlreadyUsed
     */
    public function save(Account $account): void;
}

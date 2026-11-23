<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Repository;

use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\SharedKernel\Domain\Contact\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

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

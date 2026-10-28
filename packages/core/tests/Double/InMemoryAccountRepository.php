<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;

final readonly class InMemoryAccountRepository implements AccountRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(AccountId $id): Account
    {
        return $this->database->account((string) $id) ?? throw UnknownAccount::identifiedBy($id);
    }

    public function save(Account $account): void
    {
        $this->database->storeAccount($account);
    }
}

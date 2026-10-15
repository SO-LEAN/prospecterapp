<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\UnknownAccount;
use Prospecter\Core\SharedKernel\Domain\AccountId;

final readonly class InMemoryAccountRepository implements AccountRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function nextIdentity(): AccountId
    {
        return new AccountId($this->database->uuids->next());
    }

    public function get(AccountId $id): Account
    {
        return $this->database->account((string) $id) ?? throw new UnknownAccount($id);
    }

    public function save(Account $account): void
    {
        $this->database->storeAccount($account);
    }
}

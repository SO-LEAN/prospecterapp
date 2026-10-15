<?php

namespace App\Accounts\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\SharedKernel\Domain\AccountId;

final readonly class DoctrineAccountRepository implements AccountRepository
{
    public function __construct(
        private EntityManagerInterface $accountsEntityManager,
    ) {
    }

    public function get(AccountId $id): Account
    {
        return $this->accountsEntityManager->find(Account::class, $id->value) ?? throw UnknownAccount::identifiedBy($id);
    }

    public function save(Account $account): void
    {
        $this->accountsEntityManager->persist($account);
        $this->accountsEntityManager->flush();
    }
}

<?php

namespace App\Accounts\Persistence;

use App\Shared\Persistence\UniqueEmails;
use Doctrine\ORM\EntityManagerInterface;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\UnknownAccount;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineAccountRepository implements AccountRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UniqueEmails $uniqueEmails,
    ) {
    }

    public function nextIdentity(): AccountId
    {
        return new AccountId(Uuid::v7()->toRfc4122());
    }

    public function get(AccountId $id): Account
    {
        return $this->entityManager->find(Account::class, $id->value) ?? throw new UnknownAccount($id);
    }

    public function save(Account $account): void
    {
        $this->uniqueEmails->assertFree($account->contactDetails()->email, $account->id());
        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }
}

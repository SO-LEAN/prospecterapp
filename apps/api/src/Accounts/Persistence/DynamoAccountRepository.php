<?php

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The accounts in the table of Accounts (ADR 0048): `ACCOUNT#<id>` / `ACCOUNT`, the account as a document.
 */
final readonly class DynamoAccountRepository implements AccountRepository
{
    private const string ACCOUNT = 'ACCOUNT';

    public function __construct(private Table $accountsTable)
    {
    }

    public function get(AccountId $id): Account
    {
        $item = $this->accountsTable->item(self::keyOf($id), self::ACCOUNT) ?? throw UnknownAccount::identifiedBy($id);
        $document = $item['account'];

        return Hydrator::restore(Account::class, [
            'id' => new AccountId($document['id']),
            'legalIdentity' => Documents::legalIdentityOf($document['legalIdentity']),
            'contactDetails' => Documents::contactDetailsOf($document['contactDetails']),
            'logo' => Documents::fileOf($document['logo']),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    public function save(Account $account): void
    {
        $state = Hydrator::stateOf($account);

        $this->accountsTable->transact([$this->accountsTable->putting(['PK' => self::keyOf($account->id()), 'SK' => self::ACCOUNT, 'account' => [
            'id' => (string) $state['id'],
            'legalIdentity' => Documents::legalIdentity($state['legalIdentity']),
            'contactDetails' => Documents::contactDetails($state['contactDetails']),
            'logo' => Documents::file($state['logo']),
            'createdAt' => Documents::moment($state['createdAt']),
            'modifiedAt' => Documents::moment($state['modifiedAt']),
        ]])]);
    }

    private static function keyOf(AccountId $id): string
    {
        return self::ACCOUNT.'#'.$id->value;
    }
}

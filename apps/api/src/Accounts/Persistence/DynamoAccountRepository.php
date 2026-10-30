<?php

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The accounts in the table of Accounts (ADR 0048): `ACCOUNT#<id>` / `ACCOUNT`, the account as a document.
 */
final readonly class DynamoAccountRepository implements AccountRepository
{
    public function __construct(private Table $accountsTable)
    {
    }

    public function get(AccountId $id): Account
    {
        $item = $this->accountsTable->item(AccountsKeys::account($id->value), AccountsKeys::ACCOUNT) ?? throw UnknownAccount::identifiedBy($id);
        $document = $item['account'];

        return Hydrator::restore(Account::class, [
            'id' => new AccountId($document['id']),
            'legalIdentity' => Documents::legalIdentityOf($document['legalIdentity']),
            'contactDetails' => Documents::contactDetailsOf($document['contactDetails']),
            'logo' => Documents::fileOf($document['logo']),
            'address' => Documents::addressOf($document['address'] ?? null),
            'geoPoint' => Documents::geoPointOf($document['geoPoint'] ?? null),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    public function save(Account $account): void
    {
        $state = Hydrator::stateOf($account);

        $this->accountsTable->transact([$this->accountsTable->putting(['PK' => AccountsKeys::account($account->id()->value), 'SK' => AccountsKeys::ACCOUNT, 'account' => [
            'id' => (string) $state['id'],
            'legalIdentity' => Documents::legalIdentity($state['legalIdentity']),
            'contactDetails' => Documents::contactDetails($state['contactDetails']),
            'logo' => Documents::file($state['logo']),
            'address' => Documents::address($state['address']),
            'geoPoint' => Documents::geoPoint($state['geoPoint']),
            'createdAt' => Documents::moment($state['createdAt']),
            'modifiedAt' => Documents::moment($state['modifiedAt']),
        ]])]);
    }
}

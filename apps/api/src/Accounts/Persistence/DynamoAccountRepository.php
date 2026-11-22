<?php

declare(strict_types=1);

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;

/**
 * The accounts in the table of Accounts (ADR 0048): `ACCOUNT#<id>` / `ACCOUNT`, the account as a document, listed
 * with every account in `GSI1` (`ACCOUNTS`, its id).
 */
final readonly class DynamoAccountRepository implements AccountRepository
{
    public function __construct(private Table $accountsTable)
    {
    }

    public function get(AccountId $id): Account
    {
        $item = $this->accountsTable->item(AccountsKeys::account($id->value), AccountsKeys::ACCOUNT) ?? throw UnknownAccount::identifiedBy($id);
        $document = Typed::document($item['account']);

        return Hydrator::restore(Account::class, [
            'id' => new AccountId(Typed::string($document['id'])),
            'legalIdentity' => Documents::legalIdentityOf($document['legalIdentity']),
            'contactDetails' => Documents::contactDetailsOf($document['contactDetails']),
            'logo' => Documents::fileOf($document['logo']),
            'address' => Documents::addressOf($document['address'] ?? null),
            'geoPoint' => Documents::geoPointOf($document['geoPoint'] ?? null),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
        ]);
    }

    public function save(Account $account): void
    {
        $state = Hydrator::stateOf($account);

        $this->accountsTable->transact([$this->accountsTable->putting(self::itemOf([
            'id' => Typed::object($state['id'], AccountId::class)->value,
            'legalIdentity' => Documents::legalIdentity(Typed::object($state['legalIdentity'], LegalIdentity::class)),
            'contactDetails' => Documents::contactDetails(Typed::object($state['contactDetails'], ContactDetails::class)),
            'logo' => Documents::file(Typed::objectOrNull($state['logo'], File::class)),
            'address' => Documents::address(Typed::objectOrNull($state['address'], Address::class)),
            'geoPoint' => Documents::geoPoint(Typed::objectOrNull($state['geoPoint'], GeoPoint::class)),
            'createdAt' => Documents::moment(Typed::object($state['createdAt'], \DateTimeImmutable::class)),
            'modifiedAt' => Documents::moment(Typed::object($state['modifiedAt'], \DateTimeImmutable::class)),
        ]))]);
    }

    /**
     * Writes again the item of every account, its key in the list of the accounts with it: what a table written
     * before the accounts were listed needs once.
     */
    public function writeTheKeysAgain(): int
    {
        $items = $this->accountsTable->scan(AccountsKeys::ACCOUNT.'#');
        $this->accountsTable->batch(array_map(static fn (array $item): array => self::itemOf(Typed::document($item['account'])), $items));

        return count($items);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array{PK: string, SK: string, ...}
     */
    private static function itemOf(array $document): array
    {
        $id = Typed::string($document['id']);

        return [
            'PK' => AccountsKeys::account($id),
            'SK' => AccountsKeys::ACCOUNT,
            'GSI1PK' => AccountsKeys::ALL_ACCOUNTS,
            'GSI1SK' => $id,
            'account' => $document,
        ];
    }
}

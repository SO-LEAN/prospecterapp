<?php

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Accounts\Application\MyAccount;
use Prospecter\Core\Accounts\Application\Port\MyAccountRetriever;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\Model\AccountIntent;
use Prospecter\Core\Accounts\Domain\Policy\AccountPossibilities;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the items of the member (`MEMBER#<id>` / `MEMBER`) and of their account (`ACCOUNT#<id>` / `ACCOUNT`) the
 * repositories write, without restoring them (ADR 0088).
 */
final readonly class DynamoMyAccountRetriever implements MyAccountRetriever
{
    public function __construct(private Table $accountsTable)
    {
    }

    public function of(MemberId $me): MyAccount
    {
        $member = ($this->accountsTable->item(AccountsKeys::member($me->value), AccountsKeys::MEMBER) ?? throw UnknownMember::identifiedBy($me))['member'];
        $account = ($this->accountsTable->item(AccountsKeys::account($member['accountId']), AccountsKeys::ACCOUNT) ?? throw UnknownAccount::identifiedBy(new AccountId($member['accountId'])))['account'];
        $identity = Documents::legalIdentityOf($account['legalIdentity']);
        $address = Documents::addressOf($account['address'] ?? null);
        $geoPoint = Documents::geoPointOf($account['geoPoint'] ?? null);

        return new MyAccount(
            memberId: $member['id'],
            accountId: $account['id'],
            personId: $member['personId'],
            roles: $member['roles'],
            organizationCorporateName: $identity->corporateName,
            organizationForm: $identity->form,
            organizationType: $identity->type,
            organizationLogo: FileResponse::orNone(Documents::fileOf($account['logo'])),
            organizationStreet: $address?->street,
            organizationPostalCode: $address?->postalCode,
            organizationCity: $address?->city,
            organizationCountry: $address?->country,
            organizationLongitude: $geoPoint?->longitude,
            organizationLatitude: $geoPoint?->latitude,
            accountIntents: array_map(static fn (AccountIntent $intent): string => $intent->name, AccountPossibilities::given($account['id'] === $member['accountId'])),
            createdAt: Documents::momentOf($account['createdAt']),
            modifiedAt: Documents::momentOf($account['modifiedAt']),
        );
    }
}

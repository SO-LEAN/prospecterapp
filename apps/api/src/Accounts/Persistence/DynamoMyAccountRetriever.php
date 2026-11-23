<?php

declare(strict_types=1);

namespace App\Accounts\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Accounts\Application\MyAccount;
use Prospecter\Core\Accounts\Application\Port\MyAccountRetriever;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\Model\AccountIntent;
use Prospecter\Core\Accounts\Domain\Rule\AccountPossibilities;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads the member's item (`MEMBER#<id>` / `MEMBER`) and their account's item (`ACCOUNT#<id>` / `ACCOUNT`) that the
 * repositories write. It restores neither of them (ADR 0088).
 */
final readonly class DynamoMyAccountRetriever implements MyAccountRetriever
{
    public function __construct(private Table $accountsTable)
    {
    }

    public function of(MemberId $me): MyAccount
    {
        $member = Typed::document(($this->accountsTable->item(AccountsKeys::member($me->value), AccountsKeys::MEMBER) ?? throw UnknownMember::identifiedBy($me))['member']);
        $accountId = Typed::string($member['accountId']);
        $account = Typed::document(($this->accountsTable->item(AccountsKeys::account($accountId), AccountsKeys::ACCOUNT) ?? throw UnknownAccount::identifiedBy(new AccountId($accountId)))['account']);
        $identity = Documents::legalIdentityOf($account['legalIdentity']);
        $address = Documents::addressOf($account['address'] ?? null);
        $geoPoint = Documents::geoPointOf($account['geoPoint'] ?? null);

        return new MyAccount(
            memberId: Typed::string($member['id']),
            accountId: Typed::string($account['id']),
            personId: Typed::string($member['personId']),
            roles: Typed::strings($member['roles']),
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
            accountIntents: array_map(static fn (AccountIntent $intent): string => $intent->name, AccountPossibilities::given($account['id'] === $accountId)),
            createdAt: Documents::momentOf(Typed::string($account['createdAt'])),
            modifiedAt: Documents::momentOf(Typed::string($account['modifiedAt'])),
        );
    }
}

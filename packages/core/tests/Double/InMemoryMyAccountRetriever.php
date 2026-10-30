<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Accounts\Application\MyAccount;
use Prospecter\Core\Accounts\Application\Port\MyAccountRetriever;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\Accounts\Domain\Model\AccountIntent;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Policy\AccountPossibilities;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the members and the accounts the in-memory repositories saved, as the adapter reads the items of the
 * table.
 */
final readonly class InMemoryMyAccountRetriever implements MyAccountRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function of(MemberId $me): MyAccount
    {
        $member = $this->database->member((string) $me) ?? throw UnknownMember::identifiedBy($me);
        $account = $this->database->account((string) $member->accountId()) ?? throw UnknownAccount::identifiedBy($member->accountId());
        $identity = $account->legalIdentity();
        $address = $account->address();
        $geoPoint = $account->geoPoint();

        return new MyAccount(
            memberId: $member->id()->value,
            accountId: $account->id()->value,
            personId: $member->personId()->value,
            roles: array_map(static fn (Role $role): string => $role->value, $member->roles()),
            organizationCorporateName: $identity->corporateName,
            organizationForm: $identity->form,
            organizationType: $identity->type,
            organizationLogo: FileResponse::orNone($account->logo()),
            organizationStreet: $address?->street,
            organizationPostalCode: $address?->postalCode,
            organizationCity: $address?->city,
            organizationCountry: $address?->country,
            organizationLongitude: $geoPoint?->longitude,
            organizationLatitude: $geoPoint?->latitude,
            accountIntents: array_map(static fn (AccountIntent $intent): string => $intent->name, AccountPossibilities::given($account->id()->equals($member->accountId()))),
            createdAt: $account->createdAt(),
            modifiedAt: $account->modifiedAt(),
        );
    }
}

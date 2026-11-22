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
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

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
        $memberState = StateOf::the($member);
        $state = StateOf::the($account);
        $identity = $state->object('legalIdentity', LegalIdentity::class);
        $address = $state->objectOrNull('address', Address::class);
        $geoPoint = $state->objectOrNull('geoPoint', GeoPoint::class);

        return new MyAccount(
            memberId: $member->id()->value,
            accountId: $account->id()->value,
            personId: $memberState->object('personId', PersonId::class)->value,
            roles: array_map(static fn (Role $role): string => $role->value, $memberState->listOf('roles', Role::class)),
            organizationCorporateName: $identity->corporateName,
            organizationForm: $identity->form,
            organizationType: $identity->type,
            organizationLogo: FileResponse::orNone($state->objectOrNull('logo', File::class)),
            organizationStreet: $address?->street,
            organizationPostalCode: $address?->postalCode,
            organizationCity: $address?->city,
            organizationCountry: $address?->country,
            organizationLongitude: $geoPoint?->longitude,
            organizationLatitude: $geoPoint?->latitude,
            accountIntents: array_map(static fn (AccountIntent $intent): string => $intent->name, AccountPossibilities::given($account->id()->equals($member->accountId()))),
            createdAt: $state->object('createdAt', \DateTimeImmutable::class),
            modifiedAt: $state->object('modifiedAt', \DateTimeImmutable::class),
        );
    }
}

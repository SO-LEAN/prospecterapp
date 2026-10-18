<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application;

use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountIntent;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * My account: who I am (my identities, my roles), the identity, logo and address of my organization, what I may change
 * of it. The delivery also reads it to know who calls it.
 */
final readonly class AccountResponse
{
    /**
     * @param list<string> $roles          values of my roles: ROLE_USER, ROLE_PROSPECTOR...
     * @param list<string> $accountIntents names of what I may change of my organization: ReviseLegalIdentity, ChangeLogo, Relocate
     */
    private function __construct(
        public string $memberId,
        public string $accountId,
        public string $personId,
        public array $roles,
        public ?string $organizationCorporateName,
        public ?string $organizationForm,
        public ?string $organizationType,
        public ?FileResponse $organizationLogo,
        public ?string $organizationStreet,
        public ?string $organizationPostalCode,
        public ?string $organizationCity,
        public ?string $organizationCountry,
        public ?float $organizationLongitude,
        public ?float $organizationLatitude,
        public array $accountIntents,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $modifiedAt,
    ) {
    }

    public static function of(Account $myAccount, Member $me): self
    {
        $identity = $myAccount->legalIdentity();
        $address = $myAccount->address();
        $geoPoint = $myAccount->geoPoint();

        return new self(
            memberId: $me->id()->value,
            accountId: $myAccount->id()->value,
            personId: $me->personId()->value,
            roles: array_map(static fn (Role $role): string => $role->value, $me->roles()),
            organizationCorporateName: $identity->corporateName,
            organizationForm: $identity->form,
            organizationType: $identity->type,
            organizationLogo: FileResponse::orNone($myAccount->logo()),
            organizationStreet: $address?->street,
            organizationPostalCode: $address?->postalCode,
            organizationCity: $address?->city,
            organizationCountry: $address?->country,
            organizationLongitude: $geoPoint?->longitude,
            organizationLatitude: $geoPoint?->latitude,
            accountIntents: array_map(static fn (AccountIntent $intent): string => $intent->name, $myAccount->possibleIntentsOf($me)),
            createdAt: $myAccount->createdAt(),
            modifiedAt: $myAccount->modifiedAt(),
        );
    }
}

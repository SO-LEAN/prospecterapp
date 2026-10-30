<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application;

use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * My account: who I am (my identities, my roles), the identity, logo and address of my organization, what I may change
 * of it. The delivery also reads it to know who calls it.
 */
final readonly class MyAccount
{
    /**
     * @param list<string> $roles          values of my roles: ROLE_USER, ROLE_PROSPECTOR...
     * @param list<string> $accountIntents names of what I may change of my organization: ReviseLegalIdentity, ChangeLogo, Relocate
     */
    public function __construct(
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
}

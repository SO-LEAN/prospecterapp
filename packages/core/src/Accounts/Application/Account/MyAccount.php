<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Account;

use Prospecter\Core\SharedKernel\Application\Response\FileResponse;

/**
 * My account: who I am (my ids and my roles), my organization's identity, logo and address, and what I may change in
 * it. The delivery also reads it to know who the caller is.
 */
final readonly class MyAccount
{
    /**
     * @param list<string> $roles          my roles' values: ROLE_USER, ROLE_PROSPECTOR...
     * @param list<string> $accountIntents the names of what I may change in my organization: ReviseLegalIdentity, ChangeLogo, Relocate
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

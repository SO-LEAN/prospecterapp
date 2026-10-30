<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * The sheet of an organization of an account, with what its state offers now.
 */
final readonly class OrganizationSheet
{
    /**
     * @param list<string> $contactIds      the persons prospected in it, to read in PersonalData
     * @param list<string> $possibleIntents names of the intentions its state offers now: Relocate, RemoveLogo...
     */
    public function __construct(
        public string $id,
        public string $accountId,
        public string $createdBy,
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
        public string $fullName,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
        public ?float $longitude,
        public ?float $latitude,
        public ?string $observations,
        public ?FileResponse $logo,
        public ?string $holdingId,
        public array $contactIds,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $modifiedAt,
        public array $possibleIntents,
    ) {
    }
}

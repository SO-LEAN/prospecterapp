<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\SharedKernel\Application\ExternalLink;
use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * An account's organization as its sheet shows it. It says what the organization's state offers now, and whether an
 * AI created it.
 */
final readonly class OrganizationSheet
{
    /**
     * @param list<ExternalLink>      $links              its links to outside tools
     * @param list<string>            $contactIds         the people prospected in it, to read in PersonalData
     * @param list<string>            $possibleIntents    the names of the intents its state offers now, such as Relocate or RemoveLogo
     * @param list<PossibleDuplicate> $possibleDuplicates the account's organizations it may be a duplicate of
     */
    public function __construct(
        public string $id,
        public string $accountId,
        public string $createdBy,
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
        public string $fullName,
        public ?string $siret,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public array $links,
        public ?string $website,
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
        public ?float $longitude,
        public ?float $latitude,
        public ?string $municipalityCode,
        public ?string $observations,
        public ?FileResponse $logo,
        public ?string $holdingId,
        public array $contactIds,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $modifiedAt,
        public array $possibleIntents,
        public array $possibleDuplicates,
        public bool $createdByAnAI = false,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\Prospecting\Application\PossibleDuplicate;
use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * An organization in a list: enough to recognize it and to place it on a map, and the organizations it may be a
 * duplicate of.
 */
final readonly class FoundOrganization
{
    /**
     * @param list<PossibleDuplicate> $possibleDuplicates
     */
    public function __construct(
        public string $id,
        public string $fullName,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
        public ?FileResponse $logo,
        public ?float $longitude,
        public ?float $latitude,
        public array $possibleDuplicates,
    ) {
    }
}

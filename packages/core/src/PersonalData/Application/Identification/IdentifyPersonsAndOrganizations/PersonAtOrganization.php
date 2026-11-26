<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\IdentifyPersonsAndOrganizations;

use Prospecter\Core\PersonalData\Application\Identification\PersonClues;

/**
 * A person as the client sees them, and the organization they work for, by its position among the call's
 * organizations, counted from 0. None when the client does not know it.
 */
final readonly class PersonAtOrganization
{
    public function __construct(
        public PersonClues $clues,
        public ?int $organization = null,
    ) {
    }
}

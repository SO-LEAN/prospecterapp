<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\IdentifyOrganizations;

/**
 * An organization of the account that the clues may designate.
 */
final readonly class CandidateResponse
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
    }
}

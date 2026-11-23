<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * An organization of the account that another one resembles by its name and its city. They may be the same
 * organization.
 */
final readonly class PossibleDuplicate
{
    public function __construct(
        public string $id,
        public string $fullName,
    ) {
    }
}

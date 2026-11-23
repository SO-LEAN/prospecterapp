<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts;

/**
 * Asks for the facts of the organizations an account prospects, newest first, one slice after a cursor (ADR 0049).
 * Another context needs them to count the organizations again from the state.
 */
final readonly class ListOrganizationFacts
{
    public function __construct(
        public string $accountId,
        public ?string $after,
        public int $max,
    ) {
    }
}

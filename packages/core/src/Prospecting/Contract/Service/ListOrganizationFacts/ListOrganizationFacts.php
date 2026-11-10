<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts;

/**
 * The facts of the organizations an account prospects, newest first, a slice after a cursor (ADR 0049): what
 * another context needs to count them again from the state.
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

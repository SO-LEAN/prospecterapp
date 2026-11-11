<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\ListContactFacts;

/**
 * The facts of the contacts an account keeps for its prospecting, newest first, a slice after a cursor
 * (ADR 0049): what another context needs to count them again from the state.
 */
final readonly class ListContactFacts
{
    public function __construct(
        public string $accountId,
        public ?string $after,
        public int $max,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\ListContactFacts;

/**
 * The facts about the contacts an account keeps for prospecting, newest first, one slice after a cursor (ADR 0049).
 * Another context needs them to count the contacts again from their state.
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

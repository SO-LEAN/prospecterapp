<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\IsAContactOf;

/**
 * Published to the other contexts (ADR 0045): whether a person is a contact the account prospects.
 */
final readonly class IsAContactOf
{
    public function __construct(
        public string $personId,
        public string $accountId,
    ) {
    }
}

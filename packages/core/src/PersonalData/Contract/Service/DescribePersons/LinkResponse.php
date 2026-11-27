<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\DescribePersons;

/**
 * The published language for a person's link to an outside tool: its source, what it points to there, and the address
 * that opens it (ADR 0165).
 */
final readonly class LinkResponse
{
    public function __construct(
        public string $source,
        public string $identifier,
        public string $address,
    ) {
    }
}

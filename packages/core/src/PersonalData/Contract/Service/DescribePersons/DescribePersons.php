<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\DescribePersons;

/**
 * Asks how a member reads these persons, to show them in a list (ADR 0089, 0217). It asks about a set. A single person
 * is a set of one.
 */
final readonly class DescribePersons
{
    /**
     * @param list<string> $personIds
     */
    public function __construct(
        public string $readerId,
        public array $personIds,
    ) {
    }
}

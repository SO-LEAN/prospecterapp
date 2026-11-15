<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\NameContacts;

/**
 * The names of these persons as this member reads them (ADR 0089, 0127): asked about a set, a single person being a
 * set of one.
 */
final readonly class NameContacts
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

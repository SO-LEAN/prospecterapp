<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\ListContactFacts;

/**
 * The facts of a contact, what another context counts of it: whether the account keeps it, and the revision of
 * its state, incremented at each change.
 */
final readonly class ContactFactsResponse
{
    private const int NO_REVISION = 0;

    public function __construct(
        public string $personId,
        public int $revision,
        public bool $present,
    ) {
    }

    /**
     * A forgotten contact: the last of its facts.
     */
    public static function absent(string $personId): self
    {
        return new self($personId, self::NO_REVISION, false);
    }
}

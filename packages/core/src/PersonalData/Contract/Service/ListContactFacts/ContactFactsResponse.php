<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\ListContactFacts;

/**
 * The facts of a contact, what another context counts of it: whether the account keeps it, how it is reached (an
 * e-mail, a phone, a mobile among them, a LinkedIn profile; never the details themselves, personal data), and the
 * revision of its state, incremented at each change.
 */
final readonly class ContactFactsResponse
{
    private const int NO_REVISION = 0;

    public function __construct(
        public string $personId,
        public int $revision,
        public bool $present,
        public bool $hasEmail,
        public bool $hasPhone,
        public bool $hasMobile,
        public bool $hasLinkedIn,
    ) {
    }

    /**
     * A forgotten contact: the last of its facts.
     */
    public static function absent(string $personId): self
    {
        return new self($personId, self::NO_REVISION, false, false, false, false, false);
    }
}

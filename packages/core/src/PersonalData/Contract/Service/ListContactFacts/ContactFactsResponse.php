<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\ListContactFacts;

/**
 * A contact's facts, which other contexts count. They say whether the account keeps the contact and how it is reached:
 * an e-mail, a phone, a mobile among the phones, a LinkedIn profile. They never give the details themselves, which are
 * personal data. The revision of the contact's state goes up at each change.
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
     * A forgotten contact's last facts.
     */
    public static function absent(string $personId): self
    {
        return new self($personId, self::NO_REVISION, false, false, false, false, false);
    }
}

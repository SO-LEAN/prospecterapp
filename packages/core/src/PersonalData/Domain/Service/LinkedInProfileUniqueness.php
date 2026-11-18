<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Service;

use Prospecter\Core\PersonalData\Domain\Exception\LinkedInProfileAlreadyUsed;
use Prospecter\Core\PersonalData\Domain\Model\LinkedInProfile;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;

/**
 * A LinkedIn profile designates one contact of an account, by their profile or a former one; another account, or a
 * member for their own profile, may note it (ADR 0039). A rule on all the contacts of an account, out of reach of
 * one contact alone.
 */
final readonly class LinkedInProfileUniqueness
{
    public function __construct(private PersonRepository $persons)
    {
    }

    /**
     * Once the person noted their profile, the access to them checked: a profile they held before is theirs to keep,
     * even when another contact noted it too before the profiles were unique.
     *
     * @param list<LinkedInProfile> $heldBefore
     *
     * @throws LinkedInProfileAlreadyUsed
     */
    public function ensureAvailableFor(Person $person, array $heldBefore = []): void
    {
        $profile = $person->linkedInProfile();

        if (null !== $profile && !array_any($heldBefore, static fn (LinkedInProfile $held): bool => $held->value === $profile->value)) {
            $this->ensureHeldByNoOther($person, $profile);
        }
    }

    /**
     * @throws LinkedInProfileAlreadyUsed
     */
    private function ensureHeldByNoOther(Person $person, LinkedInProfile $profile): void
    {
        if (Purpose::Prospecting !== $person->purpose()) {
            return;
        }
        $holder = $this->persons->contactHoldingLinkedInProfile($person->accountId(), $profile, except: $person->id());

        if (null !== $holder) {
            throw LinkedInProfileAlreadyUsed::heldBy($holder);
        }
    }
}

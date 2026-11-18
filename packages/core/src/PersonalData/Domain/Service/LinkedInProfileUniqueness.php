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
     * Before the person notes the profile. A profile they hold already is theirs to keep, even when another contact
     * noted it too before the profiles were unique.
     *
     * @throws LinkedInProfileAlreadyUsed
     */
    public function ensureAvailableFor(Person $person, ?LinkedInProfile $profile): void
    {
        if (null !== $profile && !$person->holdsLinkedInProfile($profile)) {
            $this->ensureHeldByNoOther($person, $profile);
        }
    }

    /**
     * A contact just collected, with the profile they hold since.
     *
     * @throws LinkedInProfileAlreadyUsed
     */
    public function ensureAvailableForANewContact(Person $contact): void
    {
        $profile = $contact->linkedInProfile();

        if (null !== $profile) {
            $this->ensureHeldByNoOther($contact, $profile);
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

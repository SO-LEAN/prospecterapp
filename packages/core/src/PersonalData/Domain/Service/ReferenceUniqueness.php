<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Service;

use Prospecter\Core\PersonalData\Domain\Exception\ReferenceAlreadyUsed;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;

/**
 * A link to an outside tool designates one contact in an account, through their current or former links. Another
 * account may log the same link, and so may a member for their own profile (ADR 0039, 0159). The rule covers all of an
 * account's contacts, which one contact alone cannot reach.
 */
final readonly class ReferenceUniqueness
{
    public function __construct(private PersonRepository $persons)
    {
    }

    /**
     * Runs once the person logged their links and the access to them was checked. A reference the person held before is
     * theirs to keep, even when another contact also logged it before references were unique.
     *
     * @param list<ExternalReference> $heldBefore
     *
     * @throws ReferenceAlreadyUsed
     */
    public function ensureAvailableFor(Person $person, array $heldBefore = []): void
    {
        if (Purpose::Prospecting !== $person->purpose()) {
            return;
        }

        foreach ($person->references()->all() as $reference) {
            if (!array_any($heldBefore, static fn (ExternalReference $held): bool => $held->key() === $reference->key())) {
                $this->ensureHeldByNoOther($person, $reference);
            }
        }
    }

    /**
     * @throws ReferenceAlreadyUsed
     */
    private function ensureHeldByNoOther(Person $person, ExternalReference $reference): void
    {
        $holder = $this->persons->contactHoldingReference($person->accountId(), $reference, except: $person->id());

        if (null !== $holder) {
            throw ReferenceAlreadyUsed::heldBy($holder);
        }
    }
}

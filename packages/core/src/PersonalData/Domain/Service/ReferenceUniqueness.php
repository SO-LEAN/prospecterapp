<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Service;

use Prospecter\Core\PersonalData\Domain\Exception\ReferenceAlreadyUsed;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;

/**
 * A link to a tool outside designates one contact of an account, by their links or their former ones; another
 * account, or a member for their own profile, may note it (ADR 0039, 0159). A rule on all the contacts of an account,
 * out of reach of one contact alone.
 */
final readonly class ReferenceUniqueness
{
    public function __construct(private PersonRepository $persons)
    {
    }

    /**
     * Once the person noted their links, the access to them checked: a reference they held before is theirs to keep,
     * even when another contact noted it too before the references were unique.
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

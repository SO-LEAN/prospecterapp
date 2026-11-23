<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Service;

use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;

/**
 * Two persons that the same account keeps for the same purpose cannot share an email. Other accounts and other purposes
 * may (ADR 0031). The rule covers all of an account's persons, which one person alone cannot reach.
 */
final readonly class EmailUniqueness
{
    public function __construct(private PersonRepository $persons)
    {
    }

    /**
     * @throws EmailAlreadyUsed
     */
    public function ensureAvailableFor(Person $person): void
    {
        $email = $person->contactDetails()->email;

        if (null !== $email && $this->persons->isEmailUsed($person->accountId(), $person->purpose(), $email, except: $person->id())) {
            throw EmailAlreadyUsed::byAnother();
        }
    }
}

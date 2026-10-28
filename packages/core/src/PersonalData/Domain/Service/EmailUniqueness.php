<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Service;

use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;

/**
 * Two persons kept by the same account for the same purpose cannot share an email; other accounts and other
 * purposes may (ADR 0031). A rule on all the persons of an account, out of reach of one person alone.
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

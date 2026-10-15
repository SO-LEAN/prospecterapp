<?php

namespace App\Prospecting;

use Prospecter\Core\PersonalData\Contract\Service\IsAContactOf\IsAContactOf;
use Prospecter\Core\Prospecting\Domain\KnownContacts;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer (ADR 0045): Prospecting asks PersonalData, through its published query, whether
 * a person is a contact of the account; an unknown person is the contact of nobody.
 */
final readonly class PersonalDataKnownContacts implements KnownContacts
{
    public function __construct(private RefusalTranslatingHandler $isAContactOf)
    {
    }

    public function isAContactOf(PersonId $person, AccountId $accountId): bool
    {
        try {
            return ($this->isAContactOf)(new IsAContactOf((string) $person, (string) $accountId));
        } catch (UseCaseRefused) {
            return false;
        }
    }
}

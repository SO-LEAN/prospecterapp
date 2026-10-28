<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Contract\Service\IsAContactOf\IsAContactOf;
use Prospecter\Core\PersonalData\Contract\Service\IsAContactOf\KeptIsAContact;
use Prospecter\Core\Prospecting\Domain\Port\KnownContacts;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045): Prospecting asks PersonalData, through its published query, whether
 * a person is a contact of the account; an unknown person is the contact of nobody.
 */
final readonly class PersonalDataKnownContacts implements KnownContacts
{
    public function __construct(private RefusalTranslatingHandler $isAContactOf)
    {
    }

    public function isAContactOf(PersonId $person, AccountId $accountId): bool
    {
        $kept = new KeptIsAContact();

        try {
            ($this->isAContactOf)(new IsAContactOf((string) $person, (string) $accountId), $kept);
        } catch (UseCaseRefused) {
            return false;
        }

        return $kept->isAContact;
    }
}

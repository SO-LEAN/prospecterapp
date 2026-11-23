<?php

declare(strict_types=1);

namespace App\Prospecting\AntiCorruption;

use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\KeptContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContactsService;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Port\KnownContacts;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Anticorruption layer (ADR 0045, 0089). Prospecting knows the persons it prospects. PersonalData tells which persons
 * are an account's contacts.
 */
final readonly class PersonalDataKnownContacts implements KnownContacts
{
    public function __construct(private WhichAreContactsService $whichAreContactsService)
    {
    }

    public function isAContactOf(ContactId $person, AccountId $accountId): bool
    {
        $kept = new KeptContacts();
        ($this->whichAreContactsService)(new WhichAreContacts([$person->value], $accountId->value), $kept);

        return in_array($person->value, $kept->personIds, true);
    }
}

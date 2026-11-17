<?php

declare(strict_types=1);

namespace App\Campaigns\AntiCorruption;

use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Port\ContactsReach;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ContactFactsResponse;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\KeptContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFactsService;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0141): the facts PersonalData gives again, slice
 * after slice, in the words of Campaigns.
 */
final readonly class PersonalDataContactsReach implements ContactsReach
{
    private const int SLICE = 100;

    public function __construct(private ListContactFactsService $listContactFactsService)
    {
    }

    public function of(AccountId $accountId): iterable
    {
        $after = null;

        do {
            $kept = new KeptContactFacts();
            ($this->listContactFactsService)(new ListContactFacts($accountId->value, $after, self::SLICE), $kept);

            yield from array_map(static fn (ContactFactsResponse $facts): ContactFact => new ContactFact(
                $accountId,
                new PersonId($facts->personId),
                $facts->revision,
                $facts->present,
                new Reach($facts->hasEmail, $facts->hasPhone, $facts->hasMobile, $facts->hasLinkedIn),
            ), $kept->facts);

            $after = $kept->next;
        } while (null !== $after);
    }
}

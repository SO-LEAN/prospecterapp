<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ContactFactsResponse;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\KeptContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFactsService;
use Prospecter\Core\Reporting\Domain\Model\ContactFact;
use Prospecter\Core\Reporting\Domain\Port\PersonalDataFacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089, 0099): the facts PersonalData gives again, slice
 * after slice, in the words of Reporting.
 */
final readonly class SlicedPersonalDataFacts implements PersonalDataFacts
{
    private const int SLICE = 100;

    public function __construct(private ListContactFactsService $listContactFactsService)
    {
    }

    public function contactsOf(AccountId $accountId): iterable
    {
        $after = null;

        do {
            $kept = new KeptContactFacts();
            ($this->listContactFactsService)(new ListContactFacts($accountId->value, $after, self::SLICE), $kept);

            yield from array_map(static fn (ContactFactsResponse $facts): ContactFact => new ContactFact($accountId, new PersonId($facts->personId), $facts->revision, $facts->present), $kept->facts);

            $after = $kept->next;
        } while (null !== $after);
    }
}

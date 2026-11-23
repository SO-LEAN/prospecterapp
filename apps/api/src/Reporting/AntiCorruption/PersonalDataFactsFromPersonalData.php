<?php

declare(strict_types=1);

namespace App\Reporting\AntiCorruption;

use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ContactFactsResponse;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\KeptContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFactsService;
use Prospecter\Core\Reporting\Domain\Model\ContactFact;
use Prospecter\Core\Reporting\Domain\Model\ContactId;
use Prospecter\Core\Reporting\Domain\Port\PersonalDataFacts;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0099). It lists the facts that PersonalData gives again, slice by slice, in
 * Reporting's terms.
 */
final readonly class PersonalDataFactsFromPersonalData implements PersonalDataFacts
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

            yield from array_map(static fn (ContactFactsResponse $facts): ContactFact => new ContactFact($accountId, new ContactId($facts->personId), $facts->revision, $facts->present), $kept->facts);

            $after = $kept->next;
        } while (null !== $after);
    }
}

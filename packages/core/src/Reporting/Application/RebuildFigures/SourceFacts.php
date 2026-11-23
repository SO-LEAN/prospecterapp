<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\RebuildFigures;

use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Port\PersonalDataFacts;
use Prospecter\Core\Reporting\Domain\Port\ProspectingFacts;
use Prospecter\Core\Reporting\Domain\Port\SourceAccounts;

/**
 * Every account's facts, as the other contexts list them again from their current state: the account's
 * organizations, exchanges and contacts.
 */
final readonly class SourceFacts
{
    public function __construct(
        private SourceAccounts $sourceAccounts,
        private ProspectingFacts $prospectingFacts,
        private PersonalDataFacts $personalDataFacts,
    ) {
    }

    /**
     * @return iterable<Fact>
     */
    public function all(): iterable
    {
        foreach ($this->sourceAccounts->all() as $accountId) {
            yield from $this->prospectingFacts->organizationsOf($accountId);
            yield from $this->prospectingFacts->exchangesOf($accountId);
            yield from $this->personalDataFacts->contactsOf($accountId);
        }
    }
}

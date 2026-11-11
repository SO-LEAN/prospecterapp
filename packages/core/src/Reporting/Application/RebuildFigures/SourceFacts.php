<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\RebuildFigures;

use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Port\PersonalDataFacts;
use Prospecter\Core\Reporting\Domain\Port\ProspectingFacts;
use Prospecter\Core\Reporting\Domain\Port\SourceAccounts;

/**
 * The facts of every account as the other contexts give them again from their state: their organizations, their
 * exchanges and their contacts.
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

<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RebuildTags;

use Prospecter\Core\Campaigns\Application\FactRecorder;
use Prospecter\Core\Campaigns\Domain\Port\ContactsReach;
use Prospecter\Core\Campaigns\Domain\Port\OrganizationsAndTheirContacts;
use Prospecter\Core\Campaigns\Domain\Port\TaggedAccounts;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Records again the facts of the organizations and of the contacts of each account, as Prospecting and PersonalData
 * give them now, through the succession the worker records by (ADR 0141): no version and no wait, the facts replacing
 * each other by their revision. Each fact is a unit of work of its own, without the unit of a command.
 */
final readonly class RebuildTagsHandler implements CommandHandler
{
    public function __construct(
        private TaggedAccounts $accounts,
        private OrganizationsAndTheirContacts $organizations,
        private ContactsReach $contacts,
        private FactRecorder $recorder,
        private UnitOfWork $unitOfWork,
    ) {
    }

    public function __invoke(RebuildTags $command): void
    {
        foreach ($this->accountsOf($command) as $accountId) {
            foreach ($this->organizations->of($accountId) as $fact) {
                $this->inItsOwnUnit(fn () => $this->recorder->record($fact));
            }

            foreach ($this->contacts->of($accountId) as $fact) {
                $this->inItsOwnUnit(fn () => $this->recorder->record($fact));
            }
        }
    }

    /**
     * @return iterable<AccountId>
     */
    private function accountsOf(RebuildTags $command): iterable
    {
        return null === $command->accountId ? $this->accounts->all() : [new AccountId($command->accountId)];
    }

    /**
     * @param \Closure(): void $step
     */
    private function inItsOwnUnit(\Closure $step): void
    {
        $this->unitOfWork->begin();

        try {
            $step();
            $this->unitOfWork->commit();
        } finally {
            $this->unitOfWork->discard();
        }
    }
}

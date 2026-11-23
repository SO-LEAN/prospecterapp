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
 * Records again the facts about each account's organizations and contacts, as Prospecting and PersonalData give them
 * now. It goes through the same succession as the worker (ADR 0141). It needs no version and no wait, because the facts
 * replace each other by revision. A rebuilt fact also replaces the kept fact at the same revision. Each fact is its own
 * unit of work, without a command's unit of work.
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
                $this->inItsOwnUnit(fn () => $this->recorder->recordAgain($fact));
            }

            foreach ($this->contacts->of($accountId) as $fact) {
                $this->inItsOwnUnit(fn () => $this->recorder->recordAgain($fact));
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

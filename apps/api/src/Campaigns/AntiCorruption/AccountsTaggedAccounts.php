<?php

declare(strict_types=1);

namespace App\Campaigns\AntiCorruption;

use Prospecter\Core\Accounts\Contract\Service\ListAccounts\KeptAccounts;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\ListAccounts;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\ListAccountsService;
use Prospecter\Core\Campaigns\Domain\Port\TaggedAccounts;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/** Anticorruption layer (ADR 0045, 0089): the accounts that Accounts lists, one slice after another. */
final readonly class AccountsTaggedAccounts implements TaggedAccounts
{
    private const int SLICE = 100;

    public function __construct(private ListAccountsService $listAccountsService)
    {
    }

    public function all(): iterable
    {
        $after = null;

        do {
            $kept = new KeptAccounts();
            ($this->listAccountsService)(new ListAccounts($after, self::SLICE), $kept);

            foreach ($kept->accountIds as $accountId) {
                yield new AccountId($accountId);
            }

            $after = $kept->next;
        } while (null !== $after);
    }
}

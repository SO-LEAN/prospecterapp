<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ReviseMyOrganizationIdentity;

use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;

final readonly class ReviseMyOrganizationIdentityHandler implements CommandHandler
{
    public function __construct(private AccountRepository $accounts, private MemberRepository $members)
    {
    }

    public function __invoke(ReviseMyOrganizationIdentity $command): void
    {
        $me = $this->members->get($command->userId);
        $myAccount = $this->accounts->get($me->accountId());
        $myAccount->reviseLegalIdentity($command->legalIdentity, by: $me);
        $this->accounts->save($myAccount);
    }
}

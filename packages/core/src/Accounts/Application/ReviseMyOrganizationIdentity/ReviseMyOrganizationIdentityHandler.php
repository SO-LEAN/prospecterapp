<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ReviseMyOrganizationIdentity;

use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\Accounts\Domain\Repository\MemberRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ReviseMyOrganizationIdentityHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private AccountRepository $accounts, private MemberRepository $members, private Events $events)
    {
    }

    public function __invoke(ReviseMyOrganizationIdentity $command): void
    {
        $me = $this->members->get(new MemberId($command->memberId));
        $myAccount = $this->accounts->get($me->accountId());
        $myAccount->reviseLegalIdentity(new LegalIdentity($command->corporateName, $command->form, $command->type), by: $me, at: $command->executedAt);
        $this->accounts->save($myAccount);
        $this->publishWhatHappenedTo($myAccount);
    }
}

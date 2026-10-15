<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyOrganizationLogo;

use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\FileStore;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\Port\Storage;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ChangeMyOrganizationLogoHandler implements CommandHandler
{
    use PublishesEvents;

    private FileStore $files;

    public function __construct(private AccountRepository $accounts, private MemberRepository $members, Storage $storage, private Events $events)
    {
        $this->files = new FileStore($storage);
    }

    public function __invoke(ChangeMyOrganizationLogo $command): void
    {
        $me = $this->members->get(new MemberId($command->memberId));
        $myAccount = $this->accounts->get($me->accountId());
        $myAccount->changeLogo($this->files->store($command->logo), by: $me, at: $command->executedAt);
        $this->accounts->save($myAccount);
        $this->publishWhatHappenedTo($myAccount);
    }
}

<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\OpenAccount;

use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class OpenAccountHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private AccountRepository $accounts, private Events $events)
    {
    }

    public function __invoke(OpenAccount $command): void
    {
        $account = Account::openBy(
            new Gesture(new AccountId($command->id), new MemberId($command->founderId), $command->executedAt),
            new ProfileId($command->founderProfileId),
            new LegalIdentity($command->corporateName, $command->form),
        );
        $this->accounts->save($account);
        $this->publishWhatHappenedTo($account);
    }
}

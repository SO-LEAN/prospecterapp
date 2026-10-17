<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\OpenAccount;

use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class OpenAccountHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private AccountRepository $accounts, private Events $events)
    {
    }

    public function __invoke(OpenAccount $command): void
    {
        $account = Account::openBy(
            new MemberId($command->founderId),
            new PersonId($command->founderProfileId),
            new AccountId($command->id),
            new LegalIdentity($command->corporateName, $command->form),
            $command->executedAt,
        );
        $this->accounts->save($account);
        $this->publishWhatHappenedTo($account);
    }
}

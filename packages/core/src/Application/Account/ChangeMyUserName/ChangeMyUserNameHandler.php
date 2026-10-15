<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyUserName;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class ChangeMyUserNameHandler implements CommandHandler
{
    public function __construct(private UserRepository $users)
    {
    }

    public function __invoke(ChangeMyUserName $command): void
    {
        $me = $this->users->get($command->userId);
        $me->changeUserName($command->userName);
        $this->users->save($me);
    }
}

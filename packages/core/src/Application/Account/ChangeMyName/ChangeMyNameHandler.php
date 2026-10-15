<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyName;

use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class ChangeMyNameHandler implements CommandHandler
{
    public function __construct(private UserRepository $users)
    {
    }

    public function __invoke(ChangeMyName $command): void
    {
        $me = $this->users->get($command->userId);
        $me->rename($command->name);
        $this->users->save($me);
    }
}
